<?php

namespace App\Filament\Resources\LeaveRequests\Schemas;

use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class LeaveRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Leave Request Information')
                    ->schema([
                        TextInput::make('request_number')
                            ->label('Request Number')
                            ->default(fn () => LeaveRequest::generateRequestNumber())
                            ->disabled()
                            ->dehydrated(),

                        Select::make('user_id')
                            ->label('Employee')
                            ->options(
                                User::whereDoesntHave('roles', fn ($q) => $q->where('name', 'super_admin'))
                                    ->pluck('name', 'id')
                            )
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('leave_type_id')
                            ->label('Leave Type')
                            ->options(LeaveType::where('is_active', true)->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required(),
                    ])
                    ->columns(3),

                Section::make('Leave Details')
                    ->schema([
                        DatePicker::make('start_date')
                            ->label('Start Date')
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                self::calculateTotalDays($get, $set);
                            }),

                        DatePicker::make('end_date')
                            ->label('End Date')
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                self::calculateTotalDays($get, $set);
                            }),

                        TextInput::make('total_days')
                            ->label('Total Days')
                            ->numeric()
                            ->disabled()
                            ->dehydrated()
                            ->suffix('days'),

                        Textarea::make('reason')
                            ->label('Reason')
                            ->required()
                            ->rows(4)
                            ->maxLength(2000)
                            ->placeholder('Please provide a reason for your leave request...')
                            ->columnSpanFull(),
                    ])
                    ->columns(3),

                Section::make('Status Information')
                    ->schema([
                        Placeholder::make('status_display')
                            ->label('Status')
                            ->content(fn ($record) => $record?->status ? $record->status->getLabel() : 'Pending'),

                        Placeholder::make('requested_by_display')
                            ->label('Requested By')
                            ->content(fn ($record) => $record?->user?->name ?? '-'),

                        Placeholder::make('approved_by_display')
                            ->label('Approved By')
                            ->content(fn ($record) => $record?->approvedBy?->name ?? '-'),

                        Placeholder::make('created_at_display')
                            ->label('Requested At')
                            ->content(fn ($record) => $record?->created_at?->format('M d, Y H:i') ?? '-'),
                    ])
                    ->columns(4)
                    ->hidden(fn ($record) => ! $record),

                Section::make('Rejection Details')
                    ->schema([
                        Placeholder::make('rejection_reason_display')
                            ->label('Rejection Reason')
                            ->content(fn ($record) => $record?->rejection_reason ?? '-'),

                        Placeholder::make('rejected_at_display')
                            ->label('Rejected At')
                            ->content(fn ($record) => $record?->rejected_at?->format('M d, Y H:i') ?? '-'),
                    ])
                    ->columns(2)
                    ->hidden(fn ($record) => $record?->status?->value !== 'rejected'),
            ]);
    }

    private static function calculateTotalDays(Get $get, Set $set): void
    {
        $start = $get('start_date');
        $end = $get('end_date');

        if ($start && $end) {
            $startDate = Carbon::parse($start);
            $endDate = Carbon::parse($end);

            if ($endDate->greaterThanOrEqualTo($startDate)) {
                $set('total_days', $startDate->diffInDays($endDate) + 1);
            }
        }
    }
}
