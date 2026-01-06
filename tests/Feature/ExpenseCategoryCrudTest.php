<?php

use App\Models\ExpenseCategory;
use App\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = User::factory()->create();
});

describe('ExpenseCategory CRUD Operations', function () {
    it('can create an expense category', function () {
        actingAs($this->user);

        $categoryData = [
            'name' => 'Office Supplies',
            'description' => 'Office supplies and stationery',
            'is_active' => true,
        ];

        $category = ExpenseCategory::create($categoryData);

        expect($category)->toBeInstanceOf(ExpenseCategory::class)
            ->and($category->name)->toBe('Office Supplies')
            ->and($category->is_active)->toBeTrue();

        $this->assertDatabaseHas('expense_categories', [
            'name' => 'Office Supplies',
        ]);
    });

    it('can read an expense category', function () {
        actingAs($this->user);

        $category = ExpenseCategory::factory()->create([
            'name' => 'Test Category',
        ]);

        $foundCategory = ExpenseCategory::find($category->id);

        expect($foundCategory)->not->toBeNull()
            ->and($foundCategory->name)->toBe('Test Category');
    });

    it('can read all expense categories', function () {
        actingAs($this->user);

        ExpenseCategory::factory()->count(5)->create();

        $categories = ExpenseCategory::all();

        expect($categories)->toHaveCount(5);
    });

    it('can update an expense category', function () {
        actingAs($this->user);

        $category = ExpenseCategory::factory()->create([
            'name' => 'Original Name',
            'is_active' => true,
        ]);

        $category->update([
            'name' => 'Updated Name',
            'is_active' => false,
        ]);

        $fresh = $category->fresh();
        expect($fresh->name)->toBe('Updated Name')
            ->and($fresh->is_active)->toBeFalse();
    });

    it('can delete an expense category', function () {
        actingAs($this->user);

        $category = ExpenseCategory::factory()->create();
        $categoryId = $category->id;

        $category->delete();

        $this->assertDatabaseMissing('expense_categories', ['id' => $categoryId]);
    });

    it('can filter active categories', function () {
        actingAs($this->user);

        ExpenseCategory::factory()->count(3)->create(['is_active' => true]);
        ExpenseCategory::factory()->count(2)->create(['is_active' => false]);

        $activeCategories = ExpenseCategory::where('is_active', true)->get();
        $inactiveCategories = ExpenseCategory::where('is_active', false)->get();

        expect($activeCategories)->toHaveCount(3)
            ->and($inactiveCategories)->toHaveCount(2);
    });

    it('has expenses relationship', function () {
        actingAs($this->user);

        $category = ExpenseCategory::factory()->create();

        expect($category->expenses())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
    });
});
