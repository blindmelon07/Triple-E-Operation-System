<?php

namespace App\Models;

use App\Enums\AccountCategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountType extends Model
{
    /** @use HasFactory<\Database\Factories\AccountTypeFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'category',
        'description',
        'is_system',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => AccountCategory::class,
            'is_system' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Scope a query to only include active account types.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to filter by category.
     */
    public function scopeCategory($query, AccountCategory $category)
    {
        return $query->where('category', $category->value);
    }

    /**
     * Scope a query to get revenue accounts.
     */
    public function scopeRevenue($query)
    {
        return $query->where('category', AccountCategory::Revenue->value);
    }

    /**
     * Scope a query to get expense accounts.
     */
    public function scopeExpense($query)
    {
        return $query->where('category', AccountCategory::Expense->value);
    }

    /**
     * Scope a query to get cost of goods sold accounts.
     */
    public function scopeCostOfGoodsSold($query)
    {
        return $query->where('category', AccountCategory::CostOfGoodsSold->value);
    }
}
