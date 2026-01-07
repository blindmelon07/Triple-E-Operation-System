<?php

namespace App\Filament\Pages;

use App\Models\Purchase;
use App\Models\Sale;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use UnitEnum;

class AgingReport extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;
    use HasPageShield;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static ?string $navigationLabel = 'Aging Report';

    protected static ?string $title = 'Accounts Receivable & Payable Aging';

    protected static string|UnitEnum|null $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 5;

    protected string $view = 'filament.pages.aging-report';

    #[Url]
    public string $activeTab = 'receivables';

    public function getAgingStats(): array
    {
        // Customer Receivables (Sales)
        $receivables = Sale::where('payment_status', '!=', 'paid')
            ->whereNotNull('due_date')
            ->with('customer')
            ->get();

        $receivablesCurrent = $receivables->filter(fn ($s) => $s->days_overdue === null)->sum('balance');
        $receivables1to30 = $receivables->filter(fn ($s) => $s->days_overdue !== null && $s->days_overdue <= 30)->sum('balance');
        $receivables31to60 = $receivables->filter(fn ($s) => $s->days_overdue !== null && $s->days_overdue > 30 && $s->days_overdue <= 60)->sum('balance');
        $receivables61to90 = $receivables->filter(fn ($s) => $s->days_overdue !== null && $s->days_overdue > 60 && $s->days_overdue <= 90)->sum('balance');
        $receivablesOver90 = $receivables->filter(fn ($s) => $s->days_overdue !== null && $s->days_overdue > 90)->sum('balance');
        $totalReceivables = $receivables->sum('balance');

        // Supplier Payables (Purchases)
        $payables = Purchase::where('payment_status', '!=', 'paid')
            ->whereNotNull('due_date')
            ->with('supplier')
            ->get();

        $payablesCurrent = $payables->filter(fn ($p) => $p->days_overdue === null)->sum('balance');
        $payables1to30 = $payables->filter(fn ($p) => $p->days_overdue !== null && $p->days_overdue <= 30)->sum('balance');
        $payables31to60 = $payables->filter(fn ($p) => $p->days_overdue !== null && $p->days_overdue > 30 && $p->days_overdue <= 60)->sum('balance');
        $payables61to90 = $payables->filter(fn ($p) => $p->days_overdue !== null && $p->days_overdue > 60 && $p->days_overdue <= 90)->sum('balance');
        $payablesOver90 = $payables->filter(fn ($p) => $p->days_overdue !== null && $p->days_overdue > 90)->sum('balance');
        $totalPayables = $payables->sum('balance');

        return [
            'receivables' => [
                'current' => $receivablesCurrent,
                '1_30' => $receivables1to30,
                '31_60' => $receivables31to60,
                '61_90' => $receivables61to90,
                'over_90' => $receivablesOver90,
                'total' => $totalReceivables,
                'overdue_count' => $receivables->filter(fn ($s) => $s->days_overdue !== null)->count(),
            ],
            'payables' => [
                'current' => $payablesCurrent,
                '1_30' => $payables1to30,
                '31_60' => $payables31to60,
                '61_90' => $payables61to90,
                'over_90' => $payablesOver90,
                'total' => $totalPayables,
                'overdue_count' => $payables->filter(fn ($p) => $p->days_overdue !== null)->count(),
            ],
        ];
    }

    public function table(Table $table): Table
    {
        if ($this->activeTab === 'receivables') {
            return $this->receivablesTable($table);
        }

        return $this->payablesTable($table);
    }

    protected function receivablesTable(Table $table): Table
    {
        return $table
            ->query(
                Sale::query()
                    ->where('payment_status', '!=', 'paid')
                    ->whereNotNull('due_date')
                    ->with('customer')
            )
            ->columns([
                TextColumn::make('id')
                    ->label('Invoice #')
                    ->formatStateUsing(fn ($state) => "INV-{$state}")
                    ->sortable(),
                TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('date')
                    ->label('Invoice Date')
                    ->date()
                    ->sortable(),
                TextColumn::make('due_date')
                    ->label('Due Date')
                    ->date()
                    ->sortable()
                    ->color(fn (Sale $record) => $record->days_overdue !== null ? 'danger' : 'success'),
                TextColumn::make('total')
                    ->label('Total')
                    ->money('PHP')
                    ->sortable(),
                TextColumn::make('amount_paid')
                    ->label('Paid')
                    ->money('PHP'),
                TextColumn::make('balance')
                    ->label('Balance')
                    ->money('PHP')
                    ->color('danger'),
                TextColumn::make('days_overdue')
                    ->label('Days Overdue')
                    ->getStateUsing(fn (Sale $record) => $record->days_overdue ?? 0)
                    ->formatStateUsing(fn ($state) => $state > 0 ? "{$state} days" : 'Current')
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'success')
                    ->sortable(),
                TextColumn::make('aging_bucket')
                    ->label('Aging')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Current' => 'success',
                        '1-30 Days' => 'warning',
                        '31-60 Days' => 'orange',
                        '61-90 Days' => 'danger',
                        'Over 90 Days' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('aging')
                    ->label('Aging Bucket')
                    ->options([
                        'current' => 'Current',
                        '1-30' => '1-30 Days',
                        '31-60' => '31-60 Days',
                        '61-90' => '61-90 Days',
                        'over-90' => 'Over 90 Days',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value']) {
                            'current' => $query->where('due_date', '>=', now()),
                            '1-30' => $query->whereBetween('due_date', [now()->subDays(30), now()->subDay()]),
                            '31-60' => $query->whereBetween('due_date', [now()->subDays(60), now()->subDays(31)]),
                            '61-90' => $query->whereBetween('due_date', [now()->subDays(90), now()->subDays(61)]),
                            'over-90' => $query->where('due_date', '<', now()->subDays(90)),
                            default => $query,
                        };
                    }),
                SelectFilter::make('customer_id')
                    ->relationship('customer', 'name')
                    ->label('Customer')
                    ->searchable()
                    ->preload(),
            ])
            ->defaultSort('due_date', 'asc');
    }

    protected function payablesTable(Table $table): Table
    {
        return $table
            ->query(
                Purchase::query()
                    ->where('payment_status', '!=', 'paid')
                    ->whereNotNull('due_date')
                    ->with('supplier')
            )
            ->columns([
                TextColumn::make('id')
                    ->label('PO #')
                    ->formatStateUsing(fn ($state) => "PO-{$state}")
                    ->sortable(),
                TextColumn::make('supplier.name')
                    ->label('Supplier')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('date')
                    ->label('Purchase Date')
                    ->date()
                    ->sortable(),
                TextColumn::make('due_date')
                    ->label('Due Date')
                    ->date()
                    ->sortable()
                    ->color(fn (Purchase $record) => $record->days_overdue !== null ? 'danger' : 'success'),
                TextColumn::make('total')
                    ->label('Total')
                    ->money('PHP')
                    ->sortable(),
                TextColumn::make('amount_paid')
                    ->label('Paid')
                    ->money('PHP'),
                TextColumn::make('balance')
                    ->label('Balance')
                    ->money('PHP')
                    ->color('danger'),
                TextColumn::make('days_overdue')
                    ->label('Days Overdue')
                    ->getStateUsing(fn (Purchase $record) => $record->days_overdue ?? 0)
                    ->formatStateUsing(fn ($state) => $state > 0 ? "{$state} days" : 'Current')
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'success')
                    ->sortable(),
                TextColumn::make('aging_bucket')
                    ->label('Aging')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Current' => 'success',
                        '1-30 Days' => 'warning',
                        '31-60 Days' => 'orange',
                        '61-90 Days' => 'danger',
                        'Over 90 Days' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('aging')
                    ->label('Aging Bucket')
                    ->options([
                        'current' => 'Current',
                        '1-30' => '1-30 Days',
                        '31-60' => '31-60 Days',
                        '61-90' => '61-90 Days',
                        'over-90' => 'Over 90 Days',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value']) {
                            'current' => $query->where('due_date', '>=', now()),
                            '1-30' => $query->whereBetween('due_date', [now()->subDays(30), now()->subDay()]),
                            '31-60' => $query->whereBetween('due_date', [now()->subDays(60), now()->subDays(31)]),
                            '61-90' => $query->whereBetween('due_date', [now()->subDays(90), now()->subDays(61)]),
                            'over-90' => $query->where('due_date', '<', now()->subDays(90)),
                            default => $query,
                        };
                    }),
                SelectFilter::make('supplier_id')
                    ->relationship('supplier', 'name')
                    ->label('Supplier')
                    ->searchable()
                    ->preload(),
            ])
            ->defaultSort('due_date', 'asc');
    }

    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetTable();
    }
}
