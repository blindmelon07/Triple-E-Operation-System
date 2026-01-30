<?php

namespace App\Filament\Resources\AuditLogs\Tables;

use App\Models\AuditLog;
use App\Models\User;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AuditLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Timestamp')
                    ->dateTime('M d, Y H:i:s')
                    ->sortable(),

                TextColumn::make('user_display_name')
                    ->label('User')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(function ($q) use ($search) {
                            $q->whereHas('user', fn ($q) => $q->where('name', 'like', "%{$search}%"))
                              ->orWhere('user_name', 'like', "%{$search}%");
                        });
                    }),

                TextColumn::make('action')
                    ->label('Action')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'created'           => 'success',
                        'updated'           => 'info',
                        'deleted'           => 'danger',
                        'login'             => 'primary',
                        'logout'            => 'gray',
                        'approved'          => 'success',
                        'rejected'          => 'danger',
                        'completed_sale'    => 'success',
                        'created_quotation' => 'info',
                        default             => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => str_replace('_', ' ', ucfirst($state))),

                TextColumn::make('auditable_type')
                    ->label('Model')
                    ->formatStateUsing(fn (?string $state): string =>
                        $state ? class_basename($state) : '-'
                    )
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('auditable_label')
                    ->label('Record')
                    ->limit(40)
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('user_agent')
                    ->label('Browser')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('user_id')
                    ->label('User')
                    ->options(fn () => User::pluck('name', 'id')->toArray())
                    ->searchable()
                    ->preload(),

                SelectFilter::make('action')
                    ->label('Action')
                    ->options([
                        'created'           => 'Created',
                        'updated'           => 'Updated',
                        'deleted'           => 'Deleted',
                        'login'             => 'Login',
                        'logout'            => 'Logout',
                        'approved'          => 'Approved',
                        'rejected'          => 'Rejected',
                        'completed_sale'    => 'Completed Sale',
                        'created_quotation' => 'Created Quotation',
                    ]),

                SelectFilter::make('auditable_type')
                    ->label('Model')
                    ->options(fn () => AuditLog::query()
                        ->whereNotNull('auditable_type')
                        ->distinct()
                        ->pluck('auditable_type')
                        ->mapWithKeys(fn ($type) => [$type => class_basename($type)])
                        ->toArray()
                    ),

                Filter::make('date_range')
                    ->form([
                        DatePicker::make('from')
                            ->label('From Date'),
                        DatePicker::make('until')
                            ->label('Until Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder =>
                                    $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder =>
                                    $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
