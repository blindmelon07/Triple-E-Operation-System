<?php

namespace App\Filament\Resources\ZkDevices\Schemas;

use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ZkDeviceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Device Details')
                    ->schema([
                        TextInput::make('serial_number')
                            ->label('Serial Number (SN)')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->helperText('Found on the device under Comm > Ethernet, or on a sticker on the unit. Devices register themselves automatically on first contact — set this to match what shows up here, or add it ahead of time.'),

                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('location')
                            ->maxLength(255)
                            ->placeholder('e.g. Main Branch - Entrance'),

                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Punches from inactive/unrecognized devices are acknowledged but not recorded as attendance.'),
                    ])
                    ->columns(2),

                Section::make('Local Bridge Access')
                    ->description('If this device has no internet/cloud push option, use these values to configure bridge/zkteco_bridge.py on a PC at the same location.')
                    ->schema([
                        TextInput::make('api_token')
                            ->label('Bridge API Token')
                            ->disabled()
                            ->dehydrated(false)
                            ->copyable()
                            ->visibleOn('edit'),
                        Placeholder::make('bridge_endpoint')
                            ->label('Bridge Endpoint URL')
                            ->content(fn () => url('/api/zkteco/attendance'))
                            ->visibleOn('edit'),
                    ])
                    ->columns(2)
                    ->visibleOn('edit'),
            ]);
    }
}
