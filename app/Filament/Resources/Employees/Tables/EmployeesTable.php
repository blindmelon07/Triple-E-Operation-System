<?php

namespace App\Filament\Resources\Employees\Tables;

use App\Enums\AttendanceLogMode;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EmployeesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable()
                    ->placeholder('Not set')
                    ->toggleable(),
                TextColumn::make('phone')
                    ->label('Phone number')
                    ->searchable()
                    ->placeholder('Not set')
                    ->toggleable(),
                TextColumn::make('biometric_pin')
                    ->label('Biometric PIN')
                    ->searchable()
                    ->placeholder('Not set')
                    ->toggleable(),
                TextColumn::make('attendance_log_mode')
                    ->label('Log Mode')
                    ->formatStateUsing(fn (?AttendanceLogMode $state) => $state?->getLabel())
                    ->badge()
                    ->toggleable(),
                TextColumn::make('user.name')
                    ->label('Login Account')
                    ->placeholder('No login')
                    ->toggleable(),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
