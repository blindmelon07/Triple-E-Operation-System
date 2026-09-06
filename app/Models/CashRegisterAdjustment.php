<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A cash top-up added to an already-open register session (e.g. the cashier
 * needed more starting change mid-shift). See CashRegisterSession::addCash().
 */
class CashRegisterAdjustment extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'cash_register_session_id',
        'user_id',
        'amount',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(CashRegisterSession::class, 'cash_register_session_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
