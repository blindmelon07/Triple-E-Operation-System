<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use Illuminate\Database\Seeder;

class ExpenseCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Utilities', 'description' => 'Electricity, water, internet, and phone bills'],
            ['name' => 'Office Supplies', 'description' => 'Paper, pens, staplers, and other office materials'],
            ['name' => 'Rent', 'description' => 'Monthly rent and lease payments'],
            ['name' => 'Salaries & Wages', 'description' => 'Employee wages and payroll'],
            ['name' => 'Transportation', 'description' => 'Fuel, vehicle maintenance, and travel expenses'],
            ['name' => 'Marketing & Advertising', 'description' => 'Advertising, promotions, and marketing materials'],
            ['name' => 'Maintenance & Repairs', 'description' => 'Equipment and building maintenance costs'],
            ['name' => 'Insurance', 'description' => 'Business insurance premiums'],
            ['name' => 'Professional Services', 'description' => 'Legal, accounting, and consulting fees'],
            ['name' => 'Taxes & Licenses', 'description' => 'Government taxes, permits, and licenses'],
            ['name' => 'Bank Charges', 'description' => 'Bank fees, transaction charges, and interest'],
            ['name' => 'Miscellaneous', 'description' => 'Other uncategorized expenses'],
        ];

        foreach ($categories as $category) {
            ExpenseCategory::firstOrCreate(
                ['name' => $category['name']],
                array_merge($category, ['is_active' => true])
            );
        }
    }
}
