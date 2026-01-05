<?php

namespace App\Enums;

enum ProductUnit: string
{
    case Piece = 'piece';
    case Liter = 'liter';
    case MilliLiter = 'milliliter';
    case Kilo = 'kilo';
    case Gram = 'gram';
    case Foot = 'foot';
    case Meter = 'meter';
    case CubicMeter = 'cubic_meter';
    case Bag = 'bag';
    case Knot = 'knot';
    case Bundle = 'bundle';
    case Box = 'box';
    case Tube = 'tube';

    public function label(): string
    {
        return match ($this) {
            self::Piece => 'Piece',
            self::Liter => 'Liter',
            self::MilliLiter => 'Milliliter',
            self::Kilo => 'Kilo',
            self::Gram => 'Gram',
            self::Foot => 'Foot',
            self::Meter => 'Meter',
            self::CubicMeter => 'Cubic Meter',
            self::Bag => 'Bag',
            self::Knot => 'Knot',
            self::Bundle => 'Bundle',
            self::Box => 'Box',
            self::Tube => 'Tube',
        };
    }

    public function getUnitType(): string
    {
        return match ($this) {
            self::Piece => 'unit',
            self::Liter, self::MilliLiter => 'liquid',
            self::Kilo, self::Gram => 'weight',
            self::Foot, self::Meter => 'length',
            self::CubicMeter => 'volume',
            self::Bag, self::Knot, self::Bundle, self::Box, self::Tube => 'package',
        };
    }
}
