<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    /** @use HasFactory<\Database\Factories\CustomerFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'payment_term_days',
        'contact_person',
        'phone',
        'email',
        'address',
        'company',
    ];

    protected function casts(): array
    {
        return [
            'payment_term_days' => 'integer',
        ];
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function getPaymentTermLabelAttribute(): string
    {
        return match ($this->payment_term_days) {
            0 => 'COD',
            default => "Net {$this->payment_term_days}",
        };
    }
}
