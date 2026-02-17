<?php

namespace App\Models;

use App\Enums\CashRegisterStatus;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashRegisterSession extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'user_id',
        'opening_amount',
        'closing_amount',
        'expected_amount',
        'discrepancy',
        'total_sales',
        'total_cash_sales',
        'total_transactions',
        'notes',
        'opened_at',
        'closed_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'opening_amount' => 'decimal:2',
            'closing_amount' => 'decimal:2',
            'expected_amount' => 'decimal:2',
            'discrepancy' => 'decimal:2',
            'total_sales' => 'decimal:2',
            'total_cash_sales' => 'decimal:2',
            'total_transactions' => 'integer',
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
            'status' => CashRegisterStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function scopeOpen($query)
    {
        return $query->where('status', CashRegisterStatus::Open);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function calculateExpected(): float
    {
        return (float) $this->opening_amount + (float) $this->total_cash_sales;
    }

    public function close(float $actualAmount, ?string $notes = null): void
    {
        $expected = $this->calculateExpected();

        $this->update([
            'closing_amount' => $actualAmount,
            'expected_amount' => $expected,
            'discrepancy' => $actualAmount - $expected,
            'notes' => $notes,
            'closed_at' => now(),
            'status' => CashRegisterStatus::Closed,
        ]);
    }

    public function addSale(float $amount, bool $isCash): void
    {
        $this->increment('total_sales', $amount);
        $this->increment('total_transactions');

        if ($isCash) {
            $this->increment('total_cash_sales', $amount);
        }
    }
}
