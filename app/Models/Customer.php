<?php

namespace App\Models;

use App\Traits\Auditable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Customer extends Model
{
    /** @use HasFactory<\Database\Factories\CustomerFactory> */
    use Auditable, HasFactory;

    protected $fillable = [
        'name',
        'payment_term_days',
        'contact_person',
        'phone',
        'email',
        'address',
        'company',
        'soa_number',
        'soa_billing_date',
        'soa_notes',
    ];

    protected function casts(): array
    {
        return [
            'payment_term_days' => 'integer',
            'soa_billing_date' => 'date',
        ];
    }

    /**
     * Suggest an SOA number in the client's format: (100 + month)-YYYYMMDD
     * followed by the customer's initials, e.g. "110-20261006DD" for DON DURAN
     * billed on Oct 6, 2026. Titles like ENGR./ARCH./MS. and tags like "(1)" are skipped.
     */
    public function suggestSoaNumber(?DateTimeInterface $billingDate = null): string
    {
        $date = Carbon::instance($billingDate ?? now());

        $titles = ['ENGR', 'ARCH', 'MR', 'MRS', 'MS', 'DR', 'ATTY', 'SIR', 'MA\'AM'];
        $initials = collect(preg_split('/\s+/', strtoupper(trim($this->name))))
            ->map(fn ($word) => rtrim($word, '.,'))
            ->reject(fn ($word) => $word === '' || in_array($word, $titles, true))
            ->map(fn ($word) => preg_replace('/[^A-Z]/', '', $word)[0] ?? '')
            ->implode('');

        return (100 + $date->month).'-'.$date->format('Ymd').$initials;
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
