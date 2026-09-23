<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VoidRequest extends Model
{
    protected $fillable = [
        'sale_id',
        'sale_item_id',
        'type',
        'requested_by_id',
        'cash_register_session_id',
        'void_reason',
        'status',
        'reviewed_by_id',
        'reviewed_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function saleItem(): BelongsTo
    {
        return $this->belongsTo(SaleItem::class);
    }

    /**
     * The replacement line(s) for an 'exchange' type request — one returned
     * item can be swapped for several replacement products.
     */
    public function exchangeItems(): HasMany
    {
        return $this->hasMany(ExchangeItem::class);
    }

    /**
     * Whether this request is to void a single line item rather than the whole
     * sale. An exchange also targets a single item, but swaps it rather than
     * removing it, so it deliberately falls outside this.
     */
    public function isItemVoid(): bool
    {
        return $this->sale_item_id !== null && ! $this->isItemExchange();
    }

    /**
     * Whether this request swaps one line item for a different product.
     */
    public function isItemExchange(): bool
    {
        return $this->type === 'exchange';
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_id');
    }

    public function cashRegisterSession(): BelongsTo
    {
        return $this->belongsTo(CashRegisterSession::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
