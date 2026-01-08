<?php

namespace App\Filament\Resources\Deliveries;

use App\Enums\DeliveryStatus;
use App\Filament\Resources\Deliveries\Pages\CreateDelivery;
use App\Filament\Resources\Deliveries\Pages\EditDelivery;
use App\Filament\Resources\Deliveries\Pages\ListDeliveries;
use App\Filament\Resources\Deliveries\Pages\ViewDelivery;
use App\Models\Delivery;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class DeliveryResource extends Resource
{
    protected static ?string $model = Delivery::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static string|UnitEnum|null $navigationGroup = 'Delivery Management';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Order & Driver')
                    ->schema([
                        Select::make('sale_id')
                            ->relationship('sale', 'id')
                            ->getOptionLabelFromRecordUsing(fn ($record) => "Order #{$record->id} - ".($record->customer?->name ?? 'Walk-in'))
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('driver_id')
                            ->relationship('driver', 'name')
                            ->searchable()
                            ->preload(),
                        Select::make('status')
                            ->options(collect(DeliveryStatus::cases())->mapWithKeys(fn ($status) => [$status->value => $status->getLabel()]))
                            ->default(DeliveryStatus::Pending->value)
                            ->required(),
                    ])->columns(3),
                Section::make('Delivery Details')
                    ->schema([
                        TextInput::make('delivery_address')
                            ->maxLength(255),
                        TextInput::make('distance_km')
                            ->numeric()
                            ->suffix('km'),
                        Textarea::make('notes')
                            ->rows(2),
                    ])->columns(3),
                Section::make('Timestamps')
                    ->schema([
                        DateTimePicker::make('assigned_at'),
                        DateTimePicker::make('picked_up_at'),
                        DateTimePicker::make('delivered_at'),
                    ])->columns(3),
                Section::make('Customer Feedback')
                    ->schema([
                        Select::make('rating')
                            ->options([
                                1 => '⭐ 1 Star',
                                2 => '⭐⭐ 2 Stars',
                                3 => '⭐⭐⭐ 3 Stars',
                                4 => '⭐⭐⭐⭐ 4 Stars',
                                5 => '⭐⭐⭐⭐⭐ 5 Stars',
                            ]),
                        Textarea::make('customer_feedback')
                            ->rows(2),
                    ])->columns(2),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Customer & Order Details')
                    ->schema([
                        TextEntry::make('sale.customer.name')
                            ->label('Customer Name')
                            ->default('Walk-in'),
                        TextEntry::make('sale.id')
                            ->label('Order #')
                            ->formatStateUsing(fn ($state) => "#{$state}"),
                        TextEntry::make('delivery_address')
                            ->label('Delivery Address'),
                        TextEntry::make('driver.name')
                            ->label('Driver')
                            ->default('Unassigned'),
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (DeliveryStatus $state): string => $state->getColor())
                            ->formatStateUsing(fn (DeliveryStatus $state): string => $state->getLabel()),
                        TextEntry::make('rating')
                            ->formatStateUsing(fn (?int $state) => $state ? str_repeat('⭐', $state) : 'No rating yet'),
                    ])->columns(3),
                Section::make('Delivered Items')
                    ->schema([
                        RepeatableEntry::make('sale.sale_items')
                            ->label('')
                            ->schema([
                                TextEntry::make('product.name')
                                    ->label('Product'),
                                TextEntry::make('quantity')
                                    ->label('Qty'),
                                TextEntry::make('price')
                                    ->label('Price')
                                    ->money('PHP'),
                            ])->columns(3),
                    ]),
                Section::make('Timestamps')
                    ->schema([
                        TextEntry::make('assigned_at')
                            ->label('Assigned')
                            ->dateTime(),
                        TextEntry::make('picked_up_at')
                            ->label('Picked Up')
                            ->dateTime(),
                        TextEntry::make('delivered_at')
                            ->label('Delivered')
                            ->dateTime(),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('Delivery #')
                    ->sortable(),
                TextColumn::make('sale.id')
                    ->label('Order #')
                    ->formatStateUsing(fn ($state) => "#{$state}")
                    ->sortable(),
                TextColumn::make('sale.customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->default('Walk-in'),
                TextColumn::make('sale.sale_items.product.name')
                    ->label('Items')
                    ->listWithLineBreaks()
                    ->bulleted()
                    ->limitList(3)
                    ->expandableLimitedList(),
                TextColumn::make('driver.name')
                    ->label('Driver')
                    ->searchable()
                    ->default('Unassigned'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (DeliveryStatus $state): string => $state->getColor())
                    ->formatStateUsing(fn (DeliveryStatus $state): string => $state->getLabel()),
                TextColumn::make('delivered_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('rating')
                    ->formatStateUsing(fn (?int $state) => $state ? str_repeat('⭐', $state) : '-')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(collect(DeliveryStatus::cases())->mapWithKeys(fn ($status) => [$status->value => $status->getLabel()])),
                SelectFilter::make('driver_id')
                    ->relationship('driver', 'name')
                    ->label('Driver')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('print')
                    ->label('Print')
                    ->icon('heroicon-o-printer')
                    ->color('success')
                    ->url(fn ($record) => route('delivery.print-receipt', $record))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDeliveries::route('/'),
            'create' => CreateDelivery::route('/create'),
            'view' => ViewDelivery::route('/{record}'),
            'edit' => EditDelivery::route('/{record}/edit'),
        ];
    }
}
