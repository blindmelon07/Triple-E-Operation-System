<?php

namespace App\Filament\Resources\Employees\Schemas;

use App\Enums\AttendanceLogMode;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class EmployeeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email(),
                TextInput::make('phone')
                    ->label('Phone number')
                    ->tel(),
                TextInput::make('biometric_pin')
                    ->label('Biometric PIN')
                    ->helperText('Enrollment number assigned to this employee on the ZKTeco device.')
                    ->unique(ignoreRecord: true),
                Select::make('attendance_log_mode')
                    ->label('Attendance Log Mode')
                    ->options([
                        AttendanceLogMode::Two->value => AttendanceLogMode::Two->getLabel(),
                        AttendanceLogMode::Four->value => AttendanceLogMode::Four->getLabel(),
                    ])
                    ->default(AttendanceLogMode::Two->value)
                    ->required()
                    ->helperText('How many times this employee punches the biometric device per day. 4 logs subtracts the middle break interval from worked hours; 2 logs does not.'),
                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true)
                    ->helperText('Inactive employees are hidden from Attendance/Leave/Compensation pickers.'),
                Select::make('user_id')
                    ->label('Linked User Account')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->helperText('Only needed if this employee also logs into TOS (e.g. an admin or cashier). Leave empty for device-only employees.'),
            ]);
    }
}
