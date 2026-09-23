<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExchangeItem extends Model
{
    protected $fillable = [
        'void_request_id',
        'product_id',
        'quantity',
        'unit',
        'unit_price',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
        ];
    }

    public function voidRequest(): BelongsTo
    {
        return $this->belongsTo(VoidRequest::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function lineTotal(): float
    {
        return round((float) $this->quantity * (float) $this->unit_price, 2);
    }
}
