<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    protected static function booted(): void
    {
        static::saved(function (Purchase $purchase) {
            $total = $purchase->purchase_items()->get()->sum(function ($item) {
                return ($item->price ?? 0) * ($item->quantity ?? 1);
            });
            if ($purchase->total !== $total) {
                $purchase->updateQuietly(['total' => $total]);
            }
        });
    }
    protected $fillable = ['supplier_id', 'date', 'total'];
    /** @use HasFactory<\Database\Factories\PurchaseFactory> */
    use HasFactory;
    public function purchase_items(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function supplier(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
