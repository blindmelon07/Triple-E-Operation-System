<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashAdvance extends Model
{
    /** @use HasFactory<\Database\Factories\CashAdvanceFactory> */
    use HasFactory, Auditable;

    protected $fillable = [
        'employee_id',
        'user_id',
        'reference_number',
        'date_granted',
        'amount',
        'deduction_per_payroll',
        'purpose',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date_granted' => 'date',
            'amount' => 'decimal:2',
            'deduction_per_payroll' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<CashAdvancePayment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(CashAdvancePayment::class);
    }

    public function totalPaid(): float
    {
        return (float) $this->payments()->effective()->sum('amount');
    }

    public function balance(): float
    {
        return round((float) $this->amount - $this->totalPaid(), 2);
    }

    /**
     * Advances with a remaining balance for one employee, oldest first —
     * the order payroll deducts them in.
     *
     * @return Collection<int, CashAdvance>
     */
    public static function outstandingFor(int $employeeId): Collection
    {
        return static::query()
            ->where('employee_id', $employeeId)
            ->withSum(['payments as paid_amount' => fn (Builder $q) => $q->effective()], 'amount')
            ->orderBy('date_granted')
            ->orderBy('id')
            ->get()
            ->filter(fn (CashAdvance $ca) => round((float) $ca->amount - (float) $ca->paid_amount, 2) > 0)
            ->values();
    }

    /**
     * Generate a unique reference number.
     */
    public static function generateReferenceNumber(): string
    {
        $prefix = 'CA';
        $date = now()->format('Ymd');
        $last = static::whereDate('created_at', today())->latest('id')->first();
        $sequence = $last ? ((int) substr($last->reference_number ?? '0000', -4)) + 1 : 1;

        return sprintf('%s-%s-%04d', $prefix, $date, $sequence);
    }
}
