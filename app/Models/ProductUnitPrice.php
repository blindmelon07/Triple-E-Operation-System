<?php

namespace App\Models;

use App\Enums\ProductUnit;
use Illuminate\Database\Eloquent\Model;

class ProductUnitPrice extends Model
{
    protected $fillable = ['product_id', 'unit', 'price', 'conversion_factor'];

    protected function casts(): array
    {
        return [
            'unit' => ProductUnit::class,
            'price' => 'decimal:2',
            'conversion_factor' => 'decimal:4',
        ];
    }

    public function product(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
