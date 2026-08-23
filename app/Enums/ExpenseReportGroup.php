<?php

namespace App\Enums;

enum ExpenseReportGroup: string
{
    case SupplierPayment = 'supplier_payment';
    case Maintenance = 'maintenance';
    case Logistics = 'logistics';
    case Utilities = 'utilities';
    case Payroll = 'payroll';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::SupplierPayment => 'Supplier Payment',
            self::Maintenance => 'Maintenance',
            self::Logistics => 'Logistics',
            self::Utilities => 'Utilities',
            self::Payroll => 'Payroll',
            self::Other => 'Others',
        };
    }

    /**
     * Order these should appear in on the transaction reports.
     *
     * @return array<self>
     */
    public static function reportOrder(): array
    {
        return [
            self::SupplierPayment,
            self::Maintenance,
            self::Logistics,
            self::Utilities,
            self::Payroll,
            self::Other,
        ];
    }
}
