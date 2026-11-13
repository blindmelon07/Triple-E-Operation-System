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

    /**
     * Peso bills and coins the cashier counts when closing the register,
     * largest first. Keys of cash_breakdown are these values as strings.
     */
    public const DENOMINATIONS = ['1000', '500', '200', '100', '50', '20', '10', '5', '1', '0.25'];

    protected $fillable = [
        'user_id',
        'opening_amount',
        'closing_amount',
        'cash_breakdown',
        'expected_amount',
        'discrepancy',
        'total_sales',
        'total_cash_sales',
        'total_transactions',
        'notes',
        'override_approved_by',
        'override_approved_at',
        'opened_at',
        'closed_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'opening_amount' => 'decimal:2',
            'closing_amount' => 'decimal:2',
            'cash_breakdown' => 'array',
            'expected_amount' => 'decimal:2',
            'discrepancy' => 'decimal:2',
            'total_sales' => 'decimal:2',
            'total_cash_sales' => 'decimal:2',
            'total_transactions' => 'integer',
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
            'override_approved_at' => 'datetime',
            'status' => CashRegisterStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function overrideApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'override_approved_by');
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
        return (float) $this->opening_amount
            + (float) $this->total_cash_sales
            + $this->totalAdjustments()
            - $this->totalCashExpenses();
    }

    /**
     * Cash paid out of the drawer for expenses during this shift — expenses
     * recorded by this session's cashier, paid in cash, while the register
     * was open. Rejected expenses are left out (same rule as the Daily
     * Transaction Report). Expenses aren't linked to a session directly, so
     * the cashier + open/close time window is what ties them to this drawer.
     */
    public function totalCashExpenses(): float
    {
        if (! $this->opened_at) {
            return 0.0;
        }

        return (float) Expense::query()
            ->where('user_id', $this->user_id)
            ->where('payment_method', 'cash')
            ->where('status', '!=', 'rejected')
            ->where('created_at', '>=', $this->opened_at)
            ->when($this->closed_at, fn ($q) => $q->where('created_at', '<=', $this->closed_at))
            ->sum('amount');
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

    /**
     * Keep only known denominations with a whole, positive piece count,
     * in DENOMINATIONS order. Returns null when nothing was counted.
     */
    public static function normalizeCashBreakdown(?array $breakdown): ?array
    {
        if (! $breakdown) {
            return null;
        }

        $clean = [];

        foreach (self::DENOMINATIONS as $denomination) {
            $count = (int) ($breakdown[$denomination] ?? 0);

            if ($count > 0) {
                $clean[$denomination] = $count;
            }
        }

        return $clean ?: null;
    }

    /**
     * Total peso value of a denomination count. Summed in centavos so
     * 0.25 coins don't pick up float rounding errors.
     */
    public static function cashBreakdownTotal(?array $breakdown): float
    {
        $centavos = 0;

        foreach ($breakdown ?? [] as $denomination => $count) {
            $centavos += (int) round(((float) $denomination) * 100) * (int) $count;
        }

        return $centavos / 100;
    }

    public function close(float $actualAmount, ?string $notes = null, ?int $overrideApprovedBy = null, ?array $cashBreakdown = null): void
    {
        $expected = $this->calculateExpected();

        $this->update([
            'closing_amount' => $actualAmount,
            'cash_breakdown' => $cashBreakdown,
            'expected_amount' => $expected,
            'discrepancy' => $actualAmount - $expected,
            'notes' => $notes,
            'override_approved_by' => $overrideApprovedBy,
            'override_approved_at' => $overrideApprovedBy ? now() : null,
            'closed_at' => now(),
            'status' => CashRegisterStatus::Closed,
        ]);
    }

    public function closingCorrections(): HasMany
    {
        return $this->hasMany(ClosingCorrection::class);
    }

    public function pendingClosingCorrection(): ?ClosingCorrection
    {
        return $this->closingCorrections()->pending()->latest()->first();
    }

    /**
     * Ask to fix the closing amount of this closed session. A manager's own
     * request is approved on the spot; anyone else's waits for a manager.
     */
    public function requestClosingCorrection(User $requester, float $correctedAmount, string $reason): ClosingCorrection
    {
        $correction = $this->closingCorrections()->create([
            'requested_by_id' => $requester->id,
            'original_closing_amount' => (float) $this->closing_amount,
            'corrected_amount' => $correctedAmount,
            'reason' => $reason,
            'status' => 'pending',
        ]);

        if ($requester->hasAnyRole(['admin', 'super_admin'])) {
            $correction->approve($requester);
        }

        return $correction;
    }

    /**
     * Replace the counted cash with a corrected amount. expected_amount was
     * fixed when the session closed, so only the discrepancy is recomputed.
     */
    public function applyClosingCorrection(float $correctedAmount): void
    {
        $this->update([
            'closing_amount' => $correctedAmount,
            'discrepancy' => $correctedAmount - (float) $this->expected_amount,
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
