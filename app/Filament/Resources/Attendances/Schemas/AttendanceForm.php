<?php

namespace App\Filament\Resources\Attendances\Schemas;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class AttendanceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Attendance Details')
                    ->schema([
                        DatePicker::make('date')
                            ->label('Date')
                            ->required()
                            ->default(now())
                            ->maxDate(now()),

                        Select::make('user_id')
                            ->label('Employee')
                            ->options(
                                User::whereDoesntHave('roles', fn ($q) => $q->where('name', 'super_admin'))
                                    ->pluck('name', 'id')
                            )
                            ->searchable()
                            ->preload()
                            ->required(),

                        TimePicker::make('time_in')
                            ->label('Time In')
                            ->seconds(false)
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                $hours = Attendance::calculateTotalHours(
                                    $get('time_in'),
                                    $get('time_out')
                                );
                                $set('total_hours', $hours);
                            }),

                        TimePicker::make('time_out')
                            ->label('Time Out')
                            ->seconds(false)
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                $hours = Attendance::calculateTotalHours(
                                    $get('time_in'),
                                    $get('time_out')
                                );
                                $set('total_hours', $hours);
                            }),

                        TextInput::make('total_hours')
                            ->label('Total Hours')
                            ->numeric()
                            ->disabled()
                            ->dehydrated()
                            ->suffix('hrs'),

                        Select::make('status')
                            ->label('Status')
                            ->options(
                                collect(AttendanceStatus::cases())
                                    ->mapWithKeys(fn ($s) => [$s->value => $s->getLabel()])
                            )
                            ->default(AttendanceStatus::Present->value)
                            ->required(),
                    ])
                    ->columns(3),

                Section::make('Additional Info')
                    ->schema([
                        Textarea::make('remarks')
                            ->label('Remarks')
                            ->rows(3)
                            ->maxLength(1000)
                            ->columnSpanFull(),
                    ]),

                Section::make('Record Info')
                    ->schema([
                        Placeholder::make('recorded_by_display')
                            ->label('Recorded By')
                            ->content(fn ($record) => $record?->recordedBy?->name ?? 'Self'),

                        Placeholder::make('created_at_display')
                            ->label('Created At')
                            ->content(fn ($record) => $record?->created_at?->format('M d, Y H:i') ?? '-'),
                    ])
                    ->columns(2)
                    ->hidden(fn ($record) => ! $record),
            ]);
    }
}
