<?php

namespace App\Filament\Resources\Incentives\Tables;

use App\Models\Incentive;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class IncentivesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date')
                    ->label('Date')
                    ->date('M d, Y')
                    ->sortable(),

                TextColumn::make('employee.name')
                    ->label('Sales Rep')
                    ->weight('bold')
                    ->description(fn (Incentive $record) => $record->reference_number)
                    ->searchable(query: fn (Builder $query, string $search) => $query
                        ->where('reference_number', 'like', "%{$search}%")
                        ->orWhereHas('employee', fn (Builder $q) => $q->where('name', 'like', "%{$search}%")))
                    ->sortable(),

                TextColumn::make('description')
                    ->label('Description')
                    ->limit(28)
                    ->tooltip(fn (Incentive $record) => $record->description),

                TextColumn::make('amount')
                    ->label('Amount')
                    ->money('PHP')
                    ->alignEnd()
                    ->sortable(),

                TextColumn::make('payout_method')
                    ->label('Payout')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => Incentive::payoutOptions()[$state] ?? $state)
                    ->color(fn (string $state) => $state === Incentive::PAYOUT_PAYROLL ? 'info' : 'gray'),

                TextColumn::make('status')
                    ->label('Status')
                    ->state(fn (Incentive $record) => $record->status())
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        Incentive::STATUS_PAID => 'success',
                        Incentive::STATUS_IN_PAYROLL => 'info',
                        default => 'warning',
                    })
                    ->description(fn (Incentive $record) => match (true) {
                        $record->status() === Incentive::STATUS_PENDING => null,
                        $record->payout_method === Incentive::PAYOUT_SEPARATE => 'Released '.$record->released_at?->format('M d, Y'),
                        default => $record->payrollItem?->payroll?->payroll_number,
                    }),
            ])
            ->filters([
                SelectFilter::make('employee_id')
                    ->label('Sales Rep')
                    ->relationship('employee', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('payout_method')
                    ->label('Payout')
                    ->options(Incentive::payoutOptions()),
            ])
            ->recordActions([
                Action::make('markReleased')
                    ->label('Mark Released')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalDescription(fn (Incentive $record) => 'Confirm ₱'.number_format((float) $record->amount, 2).' was handed to '.$record->employee?->name.'.')
                    ->visible(fn (Incentive $record) => $record->payout_method === Incentive::PAYOUT_SEPARATE && ! $record->released_at)
                    ->authorize(fn (Incentive $record) => Auth::user()->can('update', $record))
                    ->action(function (Incentive $record) {
                        $record->update(['released_at' => now(), 'released_by' => Auth::id()]);

                        Notification::make()->title('Incentive marked as released')->success()->send();
                    }),

                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ])->hidden(fn (Incentive $record) => $record->isLocked()),
            ])
            ->defaultSort('date', 'desc');
    }
}
