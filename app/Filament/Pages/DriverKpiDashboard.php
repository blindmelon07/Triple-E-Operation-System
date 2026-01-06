<?php

namespace App\Filament\Pages;

use App\Enums\DeliveryStatus;
use App\Models\Delivery;
use App\Models\Driver;
use BackedEnum;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Summarizers\Average;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class DriverKpiDashboard extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $navigationLabel = 'Driver KPI';

    protected static ?string $title = 'Driver KPI Dashboard';

    protected static string|UnitEnum|null $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 4;

    protected string $view = 'filament.pages.driver-kpi-dashboard';

    public function getKpiStats(): array
    {
        $totalDeliveries = Delivery::count();
        $completedDeliveries = Delivery::where('status', DeliveryStatus::Delivered)->count();
        $failedDeliveries = Delivery::where('status', DeliveryStatus::Failed)->count();
        $pendingDeliveries = Delivery::whereIn('status', [DeliveryStatus::Pending, DeliveryStatus::Assigned, DeliveryStatus::InTransit])->count();
        $averageRating = Delivery::whereNotNull('rating')->avg('rating');
        $activeDrivers = Driver::where('is_active', true)->count();

        $successRate = $totalDeliveries > 0 ? round(($completedDeliveries / $totalDeliveries) * 100, 1) : 0;

        // Average delivery time in minutes
        $avgDeliveryTime = Delivery::where('status', DeliveryStatus::Delivered)
            ->whereNotNull('assigned_at')
            ->whereNotNull('delivered_at')
            ->get()
            ->avg(fn ($d) => $d->assigned_at->diffInMinutes($d->delivered_at));

        return [
            'total_deliveries' => $totalDeliveries,
            'completed_deliveries' => $completedDeliveries,
            'failed_deliveries' => $failedDeliveries,
            'pending_deliveries' => $pendingDeliveries,
            'success_rate' => $successRate,
            'average_rating' => $averageRating ? round($averageRating, 1) : 'N/A',
            'active_drivers' => $activeDrivers,
            'avg_delivery_time' => $avgDeliveryTime ? round($avgDeliveryTime).' min' : 'N/A',
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Driver::query()
                    ->withCount(['deliveries as total_deliveries'])
                    ->withCount(['deliveries as completed_deliveries' => fn ($q) => $q->where('status', DeliveryStatus::Delivered)])
                    ->withCount(['deliveries as failed_deliveries' => fn ($q) => $q->where('status', DeliveryStatus::Failed)])
                    ->withAvg(['deliveries as avg_rating' => fn ($q) => $q->whereNotNull('rating')], 'rating')
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Driver')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('total_deliveries')
                    ->label('Total Deliveries')
                    ->sortable()
                    ->summarize(Sum::make()->label('Total')),
                TextColumn::make('completed_deliveries')
                    ->label('Completed')
                    ->sortable()
                    ->color('success')
                    ->summarize(Sum::make()->label('Total')),
                TextColumn::make('failed_deliveries')
                    ->label('Failed')
                    ->sortable()
                    ->color('danger')
                    ->summarize(Sum::make()->label('Total')),
                TextColumn::make('success_rate')
                    ->label('Success Rate')
                    ->getStateUsing(function (Driver $record): string {
                        $total = $record->total_deliveries;
                        if ($total === 0) {
                            return '0%';
                        }

                        return round(($record->completed_deliveries / $total) * 100, 1).'%';
                    })
                    ->color(fn (Driver $record) => $record->total_deliveries > 0 && ($record->completed_deliveries / $record->total_deliveries) >= 0.9 ? 'success' : 'warning')
                    ->sortable(),
                TextColumn::make('avg_rating')
                    ->label('Avg Rating')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 1).' ⭐' : 'N/A')
                    ->sortable()
                    ->summarize(Average::make()->label('Avg')),
                TextColumn::make('is_active')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? 'Active' : 'Inactive')
                    ->color(fn ($state) => $state ? 'success' : 'gray'),
            ])
            ->filters([
                SelectFilter::make('is_active')
                    ->options([
                        '1' => 'Active',
                        '0' => 'Inactive',
                    ])
                    ->label('Status'),
            ])
            ->defaultSort('completed_deliveries', 'desc');
    }
}
