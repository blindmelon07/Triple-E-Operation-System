<?php

use App\Models\Category;
use App\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = User::factory()->create();
});

describe('Category CRUD Operations', function () {
    it('can create a category', function () {
        actingAs($this->user);

        $categoryData = [
            'name' => 'Test Category',
        ];

        $category = Category::create($categoryData);

        expect($category)->toBeInstanceOf(Category::class)
            ->and($category->name)->toBe('Test Category')
            ->and($category->exists)->toBeTrue();

        $this->assertDatabaseHas('categories', $categoryData);
    });

    it('can read a category', function () {
        actingAs($this->user);

        $category = Category::factory()->create([
            'name' => 'Read Test Category',
        ]);

        $foundCategory = Category::find($category->id);

        expect($foundCategory)->not->toBeNull()
            ->and($foundCategory->name)->toBe('Read Test Category');
    });

    it('can read all categories', function () {
        actingAs($this->user);

        Category::factory()->count(5)->create();

        $categories = Category::all();

        expect($categories)->toHaveCount(5);
    });

    it('can update a category', function () {
        actingAs($this->user);

        $category = Category::factory()->create([
            'name' => 'Original Name',
        ]);

        $category->update(['name' => 'Updated Name']);

        expect($category->fresh()->name)->toBe('Updated Name');

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Updated Name',
        ]);
    });

    it('can delete a category', function () {
        actingAs($this->user);

        $category = Category::factory()->create();
        $categoryId = $category->id;

        $category->delete();

        $this->assertDatabaseMissing('categories', ['id' => $categoryId]);
        expect(Category::find($categoryId))->toBeNull();
    });

    it('validates name is required', function () {
        actingAs($this->user);

        expect(fn () => Category::create([]))->toThrow(\Illuminate\Database\QueryException::class);
    });
});
