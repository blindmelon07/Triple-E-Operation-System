<?php

namespace App\Enums;

enum ProductUnit: string
{
    case Piece = 'piece';
    case Liter = 'liter';
    case MilliLiter = 'milliliter';
    case Kilo = 'kilo';
    case Gram = 'gram';

    public function label(): string
    {
        return match ($this) {
            self::Piece => 'Piece',
            self::Liter => 'Liter',
            self::MilliLiter => 'Milliliter',
            self::Kilo => 'Kilo',
            self::Gram => 'Gram',
        };
    }

    public function getUnitType(): string
    {
        return match ($this) {
            self::Piece => 'unit',
            self::Liter, self::MilliLiter => 'liquid',
            self::Kilo, self::Gram => 'weight',
        };
    }
}
