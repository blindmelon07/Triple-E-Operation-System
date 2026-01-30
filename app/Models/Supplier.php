<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    /** @use HasFactory<\Database\Factories\SupplierFactory> */
    use HasFactory, Auditable;

    protected $fillable = [
        'name',
        'payment_term_days',
        'contact_person',
        'phone',
        'email',
        'address',
    ];

    protected function casts(): array
    {
        return [
            'payment_term_days' => 'integer',
        ];
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function getPaymentTermLabelAttribute(): string
    {
        return match ($this->payment_term_days) {
            0 => 'COD',
            default => "Net {$this->payment_term_days}",
        };
    }
}
