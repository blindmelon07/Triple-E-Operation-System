<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GovernmentContribution extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'type',
        'salary_from',
        'salary_to',
        'employee_share',
        'employer_share',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'salary_from' => 'decimal:2',
            'salary_to' => 'decimal:2',
            'employee_share' => 'decimal:2',
            'employer_share' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get SSS employee deduction based on monthly salary.
     */
    public static function getSssDeduction(float $monthlySalary): float
    {
        $bracket = static::where('type', 'sss')
            ->where('is_active', true)
            ->where('salary_from', '<=', $monthlySalary)
            ->where('salary_to', '>=', $monthlySalary)
            ->first();

        return $bracket ? (float) $bracket->employee_share : 0;
    }

    /**
     * Get PhilHealth employee deduction based on monthly salary.
     */
    public static function getPhilhealthDeduction(float $monthlySalary): float
    {
        $bracket = static::where('type', 'philhealth')
            ->where('is_active', true)
            ->where('salary_from', '<=', $monthlySalary)
            ->where('salary_to', '>=', $monthlySalary)
            ->first();

        return $bracket ? (float) $bracket->employee_share : 0;
    }

    /**
     * Get Pag-IBIG employee deduction based on monthly salary.
     */
    public static function getPagibigDeduction(float $monthlySalary): float
    {
        $bracket = static::where('type', 'pagibig')
            ->where('is_active', true)
            ->where('salary_from', '<=', $monthlySalary)
            ->where('salary_to', '>=', $monthlySalary)
            ->first();

        return $bracket ? (float) $bracket->employee_share : 0;
    }
}
