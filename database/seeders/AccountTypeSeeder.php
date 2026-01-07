<?php

namespace Database\Seeders;

use App\Enums\AccountCategory;
use App\Models\AccountType;
use Illuminate\Database\Seeder;

class AccountTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $accountTypes = [
            // Asset Accounts
            [
                'code' => '1000',
                'name' => 'Cash',
                'category' => AccountCategory::Asset,
                'description' => 'Cash on hand and in bank accounts',
                'is_system' => true,
            ],
            [
                'code' => '1100',
                'name' => 'Accounts Receivable',
                'category' => AccountCategory::Asset,
                'description' => 'Money owed by customers',
                'is_system' => true,
            ],
            [
                'code' => '1200',
                'name' => 'Inventory',
                'category' => AccountCategory::Asset,
                'description' => 'Products held for sale',
                'is_system' => true,
            ],
            [
                'code' => '1300',
                'name' => 'Prepaid Expenses',
                'category' => AccountCategory::Asset,
                'description' => 'Expenses paid in advance',
                'is_system' => false,
            ],
            [
                'code' => '1500',
                'name' => 'Fixed Assets',
                'category' => AccountCategory::Asset,
                'description' => 'Long-term assets like equipment and vehicles',
                'is_system' => true,
            ],

            // Liability Accounts
            [
                'code' => '2000',
                'name' => 'Accounts Payable',
                'category' => AccountCategory::Liability,
                'description' => 'Money owed to suppliers',
                'is_system' => true,
            ],
            [
                'code' => '2100',
                'name' => 'Accrued Expenses',
                'category' => AccountCategory::Liability,
                'description' => 'Expenses incurred but not yet paid',
                'is_system' => false,
            ],
            [
                'code' => '2200',
                'name' => 'Short-term Loans',
                'category' => AccountCategory::Liability,
                'description' => 'Loans payable within one year',
                'is_system' => false,
            ],
            [
                'code' => '2500',
                'name' => 'Long-term Debt',
                'category' => AccountCategory::Liability,
                'description' => 'Loans payable over one year',
                'is_system' => false,
            ],

            // Equity Accounts
            [
                'code' => '3000',
                'name' => "Owner's Equity",
                'category' => AccountCategory::Equity,
                'description' => "Owner's investment in the business",
                'is_system' => true,
            ],
            [
                'code' => '3100',
                'name' => 'Retained Earnings',
                'category' => AccountCategory::Equity,
                'description' => 'Accumulated profits reinvested in the business',
                'is_system' => true,
            ],

            // Revenue Accounts
            [
                'code' => '4000',
                'name' => 'Sales Revenue',
                'category' => AccountCategory::Revenue,
                'description' => 'Income from product sales',
                'is_system' => true,
            ],
            [
                'code' => '4100',
                'name' => 'Service Revenue',
                'category' => AccountCategory::Revenue,
                'description' => 'Income from services provided',
                'is_system' => false,
            ],
            [
                'code' => '4200',
                'name' => 'Other Income',
                'category' => AccountCategory::Revenue,
                'description' => 'Miscellaneous income',
                'is_system' => false,
            ],

            // Cost of Goods Sold
            [
                'code' => '5000',
                'name' => 'Cost of Goods Sold',
                'category' => AccountCategory::CostOfGoodsSold,
                'description' => 'Direct cost of products sold',
                'is_system' => true,
            ],
            [
                'code' => '5100',
                'name' => 'Purchase Costs',
                'category' => AccountCategory::CostOfGoodsSold,
                'description' => 'Cost of purchasing inventory',
                'is_system' => true,
            ],
            [
                'code' => '5200',
                'name' => 'Freight & Shipping',
                'category' => AccountCategory::CostOfGoodsSold,
                'description' => 'Cost of shipping goods',
                'is_system' => false,
            ],

            // Expense Accounts
            [
                'code' => '6000',
                'name' => 'Salaries & Wages',
                'category' => AccountCategory::Expense,
                'description' => 'Employee compensation',
                'is_system' => true,
            ],
            [
                'code' => '6100',
                'name' => 'Rent Expense',
                'category' => AccountCategory::Expense,
                'description' => 'Cost of renting business premises',
                'is_system' => false,
            ],
            [
                'code' => '6200',
                'name' => 'Utilities',
                'category' => AccountCategory::Expense,
                'description' => 'Electricity, water, internet expenses',
                'is_system' => false,
            ],
            [
                'code' => '6300',
                'name' => 'Office Supplies',
                'category' => AccountCategory::Expense,
                'description' => 'Office consumables and supplies',
                'is_system' => false,
            ],
            [
                'code' => '6400',
                'name' => 'Vehicle Expenses',
                'category' => AccountCategory::Expense,
                'description' => 'Fuel, maintenance, and vehicle-related costs',
                'is_system' => true,
            ],
            [
                'code' => '6500',
                'name' => 'Insurance',
                'category' => AccountCategory::Expense,
                'description' => 'Business insurance premiums',
                'is_system' => false,
            ],
            [
                'code' => '6600',
                'name' => 'Marketing & Advertising',
                'category' => AccountCategory::Expense,
                'description' => 'Promotional and marketing costs',
                'is_system' => false,
            ],
            [
                'code' => '6700',
                'name' => 'Professional Fees',
                'category' => AccountCategory::Expense,
                'description' => 'Legal, accounting, and consulting fees',
                'is_system' => false,
            ],
            [
                'code' => '6800',
                'name' => 'Depreciation',
                'category' => AccountCategory::Expense,
                'description' => 'Depreciation of fixed assets',
                'is_system' => false,
            ],
            [
                'code' => '6900',
                'name' => 'Miscellaneous Expenses',
                'category' => AccountCategory::Expense,
                'description' => 'Other general expenses',
                'is_system' => false,
            ],
        ];

        foreach ($accountTypes as $accountType) {
            AccountType::updateOrCreate(
                ['code' => $accountType['code']],
                $accountType
            );
        }
    }
}
