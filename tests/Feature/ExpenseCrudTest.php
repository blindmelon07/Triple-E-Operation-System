<?php

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = User::factory()->create();
});

describe('Expense CRUD Operations', function () {
    it('can create an expense', function () {
        actingAs($this->user);

        $category = ExpenseCategory::factory()->create();

        $expenseData = [
            'expense_category_id' => $category->id,
            'user_id' => $this->user->id,
            'reference_number' => Expense::generateReferenceNumber(),
            'expense_date' => now()->toDateString(),
            'amount' => 1500.00,
            'payment_method' => 'cash',
            'payee' => 'Test Vendor',
            'description' => 'Test expense description',
            'status' => 'pending',
        ];

        $expense = Expense::create($expenseData);

        expect($expense)->toBeInstanceOf(Expense::class)
            ->and((float) $expense->amount)->toBe(1500.00)
            ->and($expense->payee)->toBe('Test Vendor')
            ->and($expense->status)->toBe('pending');

        $this->assertDatabaseHas('expenses', [
            'payee' => 'Test Vendor',
            'amount' => 1500.00,
        ]);
    });

    it('can read an expense', function () {
        actingAs($this->user);

        $expense = Expense::factory()->create();

        $foundExpense = Expense::find($expense->id);

        expect($foundExpense)->not->toBeNull()
            ->and($foundExpense->id)->toBe($expense->id);
    });

    it('can read all expenses', function () {
        actingAs($this->user);

        Expense::factory()->count(5)->create();

        $expenses = Expense::all();

        expect($expenses)->toHaveCount(5);
    });

    it('can update an expense', function () {
        actingAs($this->user);

        $expense = Expense::factory()->create([
            'status' => 'pending',
            'amount' => 1000.00,
        ]);

        $expense->update([
            'status' => 'approved',
            'amount' => 1200.00,
        ]);

        $fresh = $expense->fresh();
        expect($fresh->status)->toBe('approved')
            ->and((float) $fresh->amount)->toBe(1200.00);
    });

    it('can delete an expense', function () {
        actingAs($this->user);

        $expense = Expense::factory()->create();
        $expenseId = $expense->id;

        $expense->delete();

        $this->assertDatabaseMissing('expenses', ['id' => $expenseId]);
    });

    it('belongs to an expense category', function () {
        actingAs($this->user);

        $category = ExpenseCategory::factory()->create(['name' => 'Transportation']);
        $expense = Expense::factory()->create(['expense_category_id' => $category->id]);

        expect($expense->category)->toBeInstanceOf(ExpenseCategory::class)
            ->and($expense->category->name)->toBe('Transportation');
    });

    it('belongs to a user', function () {
        actingAs($this->user);

        $expense = Expense::factory()->create(['user_id' => $this->user->id]);

        expect($expense->user)->toBeInstanceOf(User::class)
            ->and($expense->user->id)->toBe($this->user->id);
    });

    it('generates unique reference numbers', function () {
        actingAs($this->user);

        $ref1 = Expense::generateReferenceNumber();
        Expense::factory()->create(['reference_number' => $ref1]);
        $ref2 = Expense::generateReferenceNumber();

        expect($ref1)->not->toBe($ref2)
            ->and($ref1)->toStartWith('EXP-')
            ->and($ref2)->toStartWith('EXP-');
    });

    it('can filter by status', function () {
        actingAs($this->user);

        Expense::factory()->count(3)->create(['status' => 'approved']);
        Expense::factory()->count(2)->create(['status' => 'pending']);
        Expense::factory()->count(1)->create(['status' => 'rejected']);

        $approved = Expense::where('status', 'approved')->get();
        $pending = Expense::where('status', 'pending')->get();
        $rejected = Expense::where('status', 'rejected')->get();

        expect($approved)->toHaveCount(3)
            ->and($pending)->toHaveCount(2)
            ->and($rejected)->toHaveCount(1);
    });

    it('can filter by payment method', function () {
        actingAs($this->user);

        Expense::factory()->count(2)->create(['payment_method' => 'cash']);
        Expense::factory()->count(3)->create(['payment_method' => 'bank_transfer']);

        $cashExpenses = Expense::where('payment_method', 'cash')->get();

        expect($cashExpenses)->toHaveCount(2);
    });
});
