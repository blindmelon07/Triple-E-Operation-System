<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Sale extends Model
{
    /** @use HasFactory<\Database\Factories\SaleFactory> */
    use HasFactory, Auditable;

    protected $fillable = [
        'customer_id',
        'cash_register_session_id',
        'date',
        'total',
        'delivery_fee',
        'payment_method',
        'reference_number',
        'payment_term_days',
        'payment_status',
        'amount_paid',
        'due_date',
        'paid_date',
        'is_voided',
        'voided_at',
        'void_reason',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'due_date' => 'date',
            'paid_date' => 'date',
            'total' => 'decimal:2',
            'delivery_fee' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'is_voided' => 'boolean',
            'voided_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Sale $sale) {
            if (! $sale->due_date) {
                $saleDate = $sale->date ?? now();

                // COD payment term takes priority
                if ($sale->payment_term_days) {
                    $sale->due_date = $saleDate->copy()->addDays($sale->payment_term_days);
                } elseif ($sale->customer_id) {
                    $customer = Customer::find($sale->customer_id);
                    if ($customer && $customer->payment_term_days > 0) {
                        $sale->due_date = $saleDate->copy()->addDays($customer->payment_term_days);
                    }
                }
            }
        });
    }

    public function sale_items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function delivery(): HasOne
    {
        return $this->hasOne(Delivery::class);
    }

    public function cashRegisterSession(): BelongsTo
    {
        return $this->belongsTo(CashRegisterSession::class);
    }

    public function getBalanceAttribute(): float
    {
        return (float) $this->total - (float) $this->amount_paid;
    }

    public function getDaysOverdueAttribute(): ?int
    {
        if (! $this->due_date || $this->payment_status === 'paid') {
            return null;
        }

        $daysOverdue = now()->diffInDays($this->due_date, false);

        return $daysOverdue < 0 ? abs($daysOverdue) : null;
    }

    public function getAgingBucketAttribute(): string
    {
        $daysOverdue = $this->days_overdue;

        if ($daysOverdue === null) {
            return 'Current';
        }

        return match (true) {
            $daysOverdue <= 30 => '1-30 Days',
            $daysOverdue <= 60 => '31-60 Days',
            $daysOverdue <= 90 => '61-90 Days',
            default => 'Over 90 Days',
        };
    }

    /**
     * Void the whole sale: put its stock back, take its money back off every
     * register session that took it in, and mark it voided. Must run inside a
     * DB transaction. Returns the amount reversed off the registers.
     */
    public function voidAndReverse(?string $reason, string $movementNote): float
    {
        $this->load('sale_items.product');

        // Restore inventory. Skip any item already individually voided —
        // its stock was already restored when that item-void was approved.
        foreach ($this->sale_items as $item) {
            if ($item->is_manual || ! $item->product_id || $item->is_voided) {
                continue;
            }

            $baseQuantity = $item->quantity * $item->product->conversionFactorFor($item->unit);

            $inventory = Inventory::where('product_id', $item->product_id)->first();
            if ($inventory) {
                $inventory->increment('quantity', $baseQuantity);
            }

            InventoryMovement::create([
                'product_id'     => $item->product_id,
                'type'           => 'in',
                'quantity'       => $baseQuantity,
                'reason'         => 'Void',
                'reference_id'   => $this->id,
                'reference_type' => self::class,
                'notes'          => $movementNote,
            ]);
        }

        // Reverse every register session this sale's money ever touched. Payments
        // collected after creation (or a partial down payment logged at creation)
        // are recorded in sale_payments, each stamped with the session it was
        // added to — reverse those individually rather than assuming one session.
        $loggedPayments = SalePayment::where('sale_id', $this->id)->get();
        foreach ($loggedPayments as $payment) {
            if ($payment->cash_register_session_id) {
                $paymentSession = CashRegisterSession::find($payment->cash_register_session_id);
                if ($paymentSession) {
                    $paymentSession->reverseSale((float) $payment->amount, $payment->payment_method === 'cash');
                }
            }
        }

        // Money collected at sale creation is only logged as a sale_payments row
        // when it was a partial down payment on a credit sale. A non-credit sale
        // paid in full, or a credit sale whose down payment covered the total, adds
        // straight to its creation session with no ledger row — recover that amount
        // as whatever's left of amount_paid after accounting for logged payments.
        $unloggedInitial = round((float) $this->amount_paid - (float) $loggedPayments->sum('amount') - (float) $loggedPayments->sum('withholding_amount'), 2);
        if ($unloggedInitial > 0.01 && $this->cash_register_session_id) {
            $creationSession = CashRegisterSession::find($this->cash_register_session_id);
            if ($creationSession) {
                $creationSession->reverseSale($unloggedInitial, $this->payment_method === 'cash');
            }
        }

        $this->update([
            'is_voided'      => true,
            'voided_at'      => now(),
            'void_reason'    => $reason,
            'amount_paid'    => 0,
            'payment_status' => 'unpaid',
        ]);

        return (float) $loggedPayments->sum('amount') + max(0, $unloggedInitial);
    }
}
