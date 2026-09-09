<?php

namespace App\Filament\Resources\MaintenanceRecords\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MaintenanceRecordsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('items'))
            ->columns([
                TextColumn::make('reference_number')
                    ->label('Reference')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('maintenance_date')
                    ->label('Date')
                    ->date('M d, Y')
                    ->sortable(),

                TextColumn::make('supplier.name')
                    ->label('Supplier')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('si_number')
                    ->label('SI #')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('po_number')
                    ->label('PO #')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('items.item_name')
                    ->label('Product/Item')
                    ->listWithLineBreaks()
                    ->limitList(3)
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('vehicle.full_name')
                    ->label('Truck/Unit')
                    ->description(fn ($record) => $record->vehicle?->plate_number)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('maintenanceType.name')
                    ->label('Service Type')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('cost')
                    ->label('Amount')
                    ->money('PHP')
                    ->sortable()
                    ->summarize([
                        Sum::make()->money('PHP'),
                    ]),

                TextColumn::make('service_provider')
                    ->label('Provider')
                    ->searchable()
                    ->limit(20)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'completed' => 'success',
                        'in_progress' => 'warning',
                        'scheduled' => 'info',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'completed' => 'Completed',
                        'in_progress' => 'In Progress',
                        'scheduled' => 'Scheduled',
                        'cancelled' => 'Cancelled',
                        default => $state,
                    }),

                TextColumn::make('next_service_date')
                    ->label('Next Service')
                    ->date('M d, Y')
                    ->sortable()
                    ->color(fn ($record) => $record->next_service_date?->isPast() ? 'danger' : 'success')
                    ->toggleable(),

                TextColumn::make('user.name')
                    ->label('Recorded By')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('vehicle_id')
                    ->label('Vehicle')
                    ->relationship('vehicle', 'plate_number')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('maintenance_type_id')
                    ->label('Service Type')
                    ->relationship('maintenanceType', 'name')
                    ->preload()
                    ->multiple(),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'scheduled' => 'Scheduled',
                        'in_progress' => 'In Progress',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ]),

                Filter::make('maintenance_date')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')
                            ->label('From Date'),
                        \Filament\Forms\Components\DatePicker::make('until')
                            ->label('Until Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('maintenance_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('maintenance_date', '<=', $date),
                            );
                    }),

                Filter::make('overdue')
                    ->label('Overdue Services')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->where(function ($q) {
                        $q->whereNotNull('next_service_date')
                            ->where('next_service_date', '<', now());
                    })),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('maintenance_date', 'desc');
    }
}
