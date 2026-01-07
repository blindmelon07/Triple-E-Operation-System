<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum AccountCategory: string implements HasColor, HasIcon, HasLabel
{
    case Asset = 'asset';
    case Liability = 'liability';
    case Equity = 'equity';
    case Revenue = 'revenue';
    case Expense = 'expense';
    case CostOfGoodsSold = 'cogs';

    public function getLabel(): string
    {
        return match ($this) {
            self::Asset => 'Asset',
            self::Liability => 'Liability',
            self::Equity => 'Equity',
            self::Revenue => 'Revenue',
            self::Expense => 'Expense',
            self::CostOfGoodsSold => 'Cost of Goods Sold',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Asset => 'info',
            self::Liability => 'warning',
            self::Equity => 'primary',
            self::Revenue => 'success',
            self::Expense => 'danger',
            self::CostOfGoodsSold => 'gray',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Asset => 'heroicon-o-building-library',
            self::Liability => 'heroicon-o-credit-card',
            self::Equity => 'heroicon-o-scale',
            self::Revenue => 'heroicon-o-arrow-trending-up',
            self::Expense => 'heroicon-o-arrow-trending-down',
            self::CostOfGoodsSold => 'heroicon-o-cube',
        };
    }

    /**
     * Returns whether this category increases with debit.
     */
    public function isDebitNormal(): bool
    {
        return match ($this) {
            self::Asset, self::Expense, self::CostOfGoodsSold => true,
            self::Liability, self::Equity, self::Revenue => false,
        };
    }

    /**
     * Returns whether this category affects profit calculation.
     */
    public function affectsProfit(): bool
    {
        return match ($this) {
            self::Revenue, self::Expense, self::CostOfGoodsSold => true,
            self::Asset, self::Liability, self::Equity => false,
        };
    }
}
