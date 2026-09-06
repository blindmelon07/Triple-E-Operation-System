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

    public function adjustments(): HasMany
    {
        return $this->hasMany(CashRegisterAdjustment::class);
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
        return (float) $this->opening_amount + (float) $this->total_cash_sales + $this->totalAdjustments();
    }

    /**
     * Sum of all mid-shift cash top-ups added via addCash(). opening_amount
     * itself is never mutated after open, so this has to be added on top of it
     * wherever "how much cash should be in the drawer" is calculated.
     */
    public function totalAdjustments(): float
    {
        return (float) $this->adjustments()->sum('amount');
    }

    /**
     * Add starting money to the drawer while the register is already open
     * (e.g. the cashier ran low on change). Recorded as its own ledger entry
     * rather than changing opening_amount, so the original opening count and
     * every top-up stay individually auditable.
     */
    public function addCash(float $amount, ?string $reason, int $userId): CashRegisterAdjustment
    {
        return $this->adjustments()->create([
            'user_id' => $userId,
            'amount' => $amount,
            'reason' => $reason,
        ]);
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

    public function reverseSale(float $amount, bool $isCash): void
    {
        $this->decrement('total_sales', $amount);
        $this->decrement('total_transactions');

        if ($isCash) {
            $this->decrement('total_cash_sales', $amount);
        }
    }

    /**
     * Reverse part of a sale's collected amount without touching the
     * transaction count — the sale itself still stands, it's just for
     * less money (e.g. a single item was voided out of it).
     */
    public function reverseAmount(float $amount, bool $isCash): void
    {
        $this->decrement('total_sales', $amount);

        if ($isCash) {
            $this->decrement('total_cash_sales', $amount);
        }
    }

    /**
     * Collect extra money against a sale that already exists, without counting
     * it as a new transaction — e.g. an item was exchanged for a pricier one
     * and the customer paid the difference at the counter.
     */
    public function addAmount(float $amount, bool $isCash): void
    {
        $this->increment('total_sales', $amount);

        if ($isCash) {
            $this->increment('total_cash_sales', $amount);
        }
    }
}
