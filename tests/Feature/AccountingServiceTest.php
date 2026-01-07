<?php

use App\Models\Customer;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\MaintenanceRecord;
use App\Models\MaintenanceType;
use App\Models\Sale;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\AccountingService;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->accounting = new AccountingService;
});

describe('AccountingService', function () {
    it('can calculate total revenue', function () {
        actingAs($this->user);

        $customer = Customer::factory()->create();

        Sale::create([
            'customer_id' => $customer->id,
            'date' => now(),
            'total' => 1000.00,
            'amount_paid' => 1000.00,
            'payment_status' => 'paid',
        ]);

        Sale::create([
            'customer_id' => $customer->id,
            'date' => now(),
            'total' => 2000.00,
            'amount_paid' => 500.00,
            'payment_status' => 'partial',
        ]);

        $this->accounting->forCurrentMonth();
        $revenue = $this->accounting->getTotalRevenue();

        expect($revenue)->toBe(3000.00);
    });

    it('can calculate total collections', function () {
        actingAs($this->user);

        $customer = Customer::factory()->create();

        Sale::create([
            'customer_id' => $customer->id,
            'date' => now(),
            'total' => 1000.00,
            'amount_paid' => 800.00,
            'payment_status' => 'partial',
        ]);

        Sale::create([
            'customer_id' => $customer->id,
            'date' => now(),
            'total' => 2000.00,
            'amount_paid' => 2000.00,
            'payment_status' => 'paid',
        ]);

        $this->accounting->forCurrentMonth();
        $collections = $this->accounting->getTotalCollections();

        expect($collections)->toBe(2800.00);
    });

    it('can calculate accounts receivable', function () {
        actingAs($this->user);

        $customer = Customer::factory()->create();

        Sale::create([
            'customer_id' => $customer->id,
            'date' => now(),
            'total' => 1000.00,
            'amount_paid' => 400.00,
            'payment_status' => 'partial',
        ]);

        Sale::create([
            'customer_id' => $customer->id,
            'date' => now(),
            'total' => 500.00,
            'amount_paid' => 0.00,
            'payment_status' => 'pending',
        ]);

        // Paid sale should not be included
        Sale::create([
            'customer_id' => $customer->id,
            'date' => now(),
            'total' => 2000.00,
            'amount_paid' => 2000.00,
            'payment_status' => 'paid',
        ]);

        $receivable = $this->accounting->getAccountsReceivable();

        // 1000 - 400 + 500 - 0 = 1100
        expect($receivable)->toBe(1100.00);
    });

    it('can calculate total expenses', function () {
        actingAs($this->user);

        $category = ExpenseCategory::factory()->create();

        Expense::create([
            'expense_category_id' => $category->id,
            'user_id' => $this->user->id,
            'reference_number' => 'EXP-001',
            'expense_date' => now(),
            'amount' => 500.00,
            'payment_method' => 'cash',
            'status' => 'approved',
        ]);

        Expense::create([
            'expense_category_id' => $category->id,
            'user_id' => $this->user->id,
            'reference_number' => 'EXP-002',
            'expense_date' => now(),
            'amount' => 300.00,
            'payment_method' => 'cash',
            'status' => 'approved',
        ]);

        // Pending expense should not be included
        Expense::create([
            'expense_category_id' => $category->id,
            'user_id' => $this->user->id,
            'reference_number' => 'EXP-003',
            'expense_date' => now(),
            'amount' => 200.00,
            'payment_method' => 'cash',
            'status' => 'pending',
        ]);

        $this->accounting->forCurrentMonth();
        $expenses = $this->accounting->getTotalExpenses();

        expect($expenses)->toBe(800.00);
    });

    it('can calculate gross profit', function () {
        actingAs($this->user);

        $customer = Customer::factory()->create();

        // Create sales
        $sale = Sale::create([
            'customer_id' => $customer->id,
            'date' => now(),
            'total' => 5000.00,
            'amount_paid' => 5000.00,
            'payment_status' => 'paid',
        ]);

        $this->accounting->forCurrentMonth();

        // Gross profit without COGS (would need sale items with products)
        $grossProfit = $this->accounting->getGrossProfit();

        // Revenue - COGS (COGS is 0 if no sale items)
        expect($grossProfit)->toBe(5000.00);
    });

    it('can calculate operating profit', function () {
        actingAs($this->user);

        $customer = Customer::factory()->create();
        $category = ExpenseCategory::factory()->create();

        // Create sales
        Sale::create([
            'customer_id' => $customer->id,
            'date' => now(),
            'total' => 10000.00,
            'amount_paid' => 10000.00,
            'payment_status' => 'paid',
        ]);

        // Create expenses
        Expense::create([
            'expense_category_id' => $category->id,
            'user_id' => $this->user->id,
            'reference_number' => 'EXP-001',
            'expense_date' => now(),
            'amount' => 2000.00,
            'payment_method' => 'cash',
            'status' => 'approved',
        ]);

        $this->accounting->forCurrentMonth();
        $operatingProfit = $this->accounting->getOperatingProfit();

        // Revenue (10000) - COGS (0) - Expenses (2000) = 8000
        expect($operatingProfit)->toBe(8000.00);
    });

    it('can determine if business is profitable', function () {
        actingAs($this->user);

        $customer = Customer::factory()->create();
        $category = ExpenseCategory::factory()->create();

        // Create sales
        Sale::create([
            'customer_id' => $customer->id,
            'date' => now(),
            'total' => 10000.00,
            'amount_paid' => 10000.00,
            'payment_status' => 'paid',
        ]);

        // Create expenses less than revenue
        Expense::create([
            'expense_category_id' => $category->id,
            'user_id' => $this->user->id,
            'reference_number' => 'EXP-001',
            'expense_date' => now(),
            'amount' => 5000.00,
            'payment_method' => 'cash',
            'status' => 'approved',
        ]);

        $this->accounting->forCurrentMonth();

        expect($this->accounting->isProfitable())->toBeTrue();
    });

    it('can determine if business is at a loss', function () {
        actingAs($this->user);

        $customer = Customer::factory()->create();
        $category = ExpenseCategory::factory()->create();

        // Create small sales
        Sale::create([
            'customer_id' => $customer->id,
            'date' => now(),
            'total' => 1000.00,
            'amount_paid' => 1000.00,
            'payment_status' => 'paid',
        ]);

        // Create expenses more than revenue
        Expense::create([
            'expense_category_id' => $category->id,
            'user_id' => $this->user->id,
            'reference_number' => 'EXP-001',
            'expense_date' => now(),
            'amount' => 5000.00,
            'payment_method' => 'cash',
            'status' => 'approved',
        ]);

        $this->accounting->forCurrentMonth();

        expect($this->accounting->isProfitable())->toBeFalse();
        expect($this->accounting->getNetProfit())->toBeLessThan(0);
    });

    it('can calculate profit margin', function () {
        actingAs($this->user);

        $customer = Customer::factory()->create();
        $category = ExpenseCategory::factory()->create();

        // Create sales worth 10000
        Sale::create([
            'customer_id' => $customer->id,
            'date' => now(),
            'total' => 10000.00,
            'amount_paid' => 10000.00,
            'payment_status' => 'paid',
        ]);

        // Create expenses worth 3000 (30% of revenue)
        Expense::create([
            'expense_category_id' => $category->id,
            'user_id' => $this->user->id,
            'reference_number' => 'EXP-001',
            'expense_date' => now(),
            'amount' => 3000.00,
            'payment_method' => 'cash',
            'status' => 'approved',
        ]);

        $this->accounting->forCurrentMonth();

        // Net profit = 10000 - 3000 = 7000
        // Margin = 7000/10000 * 100 = 70%
        expect($this->accounting->getNetProfitMargin())->toBe(70.0);
    });

    it('can generate profit and loss statement', function () {
        actingAs($this->user);

        $customer = Customer::factory()->create();
        $category = ExpenseCategory::factory()->create();

        Sale::create([
            'customer_id' => $customer->id,
            'date' => now(),
            'total' => 10000.00,
            'amount_paid' => 10000.00,
            'payment_status' => 'paid',
        ]);

        Expense::create([
            'expense_category_id' => $category->id,
            'user_id' => $this->user->id,
            'reference_number' => 'EXP-001',
            'expense_date' => now(),
            'amount' => 2000.00,
            'payment_method' => 'cash',
            'status' => 'approved',
        ]);

        $this->accounting->forCurrentMonth();
        $statement = $this->accounting->getProfitAndLossStatement();

        expect($statement)->toHaveKeys([
            'period',
            'revenue',
            'cost_of_goods_sold',
            'gross_profit',
            'gross_profit_margin',
            'operating_expenses',
            'operating_profit',
            'operating_profit_margin',
            'net_profit',
            'net_profit_margin',
            'is_profitable',
        ]);

        expect($statement['revenue']['total'])->toBe(10000.00);
        expect($statement['operating_expenses']['general_expenses'])->toBe(2000.00);
        expect($statement['is_profitable'])->toBeTrue();
    });

    it('can get dashboard summary', function () {
        actingAs($this->user);

        $this->accounting->forCurrentMonth();
        $summary = $this->accounting->getDashboardSummary();

        expect($summary)->toHaveKeys([
            'revenue',
            'collections',
            'accounts_receivable',
            'purchases',
            'accounts_payable',
            'expenses',
            'maintenance_costs',
            'gross_profit',
            'gross_profit_margin',
            'operating_profit',
            'operating_profit_margin',
            'net_profit',
            'net_profit_margin',
            'is_profitable',
        ]);
    });

    it('can filter by different periods', function () {
        actingAs($this->user);

        $customer = Customer::factory()->create();

        // Sale this month
        Sale::create([
            'customer_id' => $customer->id,
            'date' => now(),
            'total' => 1000.00,
            'amount_paid' => 1000.00,
            'payment_status' => 'paid',
        ]);

        // Sale last month
        Sale::create([
            'customer_id' => $customer->id,
            'date' => now()->subMonth(),
            'total' => 2000.00,
            'amount_paid' => 2000.00,
            'payment_status' => 'paid',
        ]);

        $thisMonth = (new AccountingService)->forCurrentMonth()->getTotalRevenue();
        $lastMonth = (new AccountingService)->forLastMonth()->getTotalRevenue();

        expect($thisMonth)->toBe(1000.00);
        expect($lastMonth)->toBe(2000.00);
    });

    it('includes maintenance costs in operating expenses', function () {
        actingAs($this->user);

        $vehicle = Vehicle::factory()->create();
        $maintenanceType = MaintenanceType::factory()->create();

        MaintenanceRecord::create([
            'vehicle_id' => $vehicle->id,
            'maintenance_type_id' => $maintenanceType->id,
            'user_id' => $this->user->id,
            'reference_number' => 'MNT-001',
            'maintenance_date' => now(),
            'mileage_at_service' => 50000,
            'cost' => 500.00,
            'status' => 'completed',
        ]);

        MaintenanceRecord::create([
            'vehicle_id' => $vehicle->id,
            'maintenance_type_id' => $maintenanceType->id,
            'user_id' => $this->user->id,
            'reference_number' => 'MNT-002',
            'maintenance_date' => now(),
            'mileage_at_service' => 51000,
            'cost' => 300.00,
            'status' => 'completed',
        ]);

        // Scheduled maintenance should not be included in costs
        MaintenanceRecord::create([
            'vehicle_id' => $vehicle->id,
            'maintenance_type_id' => $maintenanceType->id,
            'user_id' => $this->user->id,
            'reference_number' => 'MNT-003',
            'maintenance_date' => now(),
            'mileage_at_service' => 52000,
            'cost' => 200.00,
            'status' => 'scheduled',
        ]);

        $this->accounting->forCurrentMonth();
        $maintenanceCosts = $this->accounting->getTotalMaintenanceCosts();

        expect($maintenanceCosts)->toBe(800.00);
    });
});
