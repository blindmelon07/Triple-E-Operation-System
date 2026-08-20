<?php

use App\Models\Employee;
use App\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = User::factory()->create();
});

describe('Employee CRUD Operations', function () {
    it('can create an employee', function () {
        actingAs($this->user);

        $employee = Employee::create([
            'name' => 'Juan Dela Cruz',
            'email' => 'juan@example.com',
            'biometric_pin' => '1234',
            'attendance_log_mode' => 'two',
            'is_active' => true,
        ]);

        expect($employee)->toBeInstanceOf(Employee::class)
            ->and($employee->name)->toBe('Juan Dela Cruz')
            ->and($employee->is_active)->toBeTrue();

        $this->assertDatabaseHas('employees', ['name' => 'Juan Dela Cruz']);
    });

    it('can read an employee', function () {
        actingAs($this->user);

        $employee = Employee::factory()->create();

        $found = Employee::find($employee->id);

        expect($found)->not->toBeNull()
            ->and($found->id)->toBe($employee->id);
    });

    it('can read all employees', function () {
        actingAs($this->user);

        Employee::factory()->count(4)->create();

        expect(Employee::all())->toHaveCount(4);
    });

    it('can update an employee', function () {
        actingAs($this->user);

        $employee = Employee::factory()->create(['is_active' => true]);

        $employee->update(['is_active' => false, 'name' => 'Updated Name']);

        $fresh = $employee->fresh();
        expect($fresh->is_active)->toBeFalse()
            ->and($fresh->name)->toBe('Updated Name');
    });

    it('can delete an employee', function () {
        actingAs($this->user);

        $employee = Employee::factory()->create();
        $id = $employee->id;

        $employee->delete();

        $this->assertDatabaseMissing('employees', ['id' => $id]);
    });

    it('can optionally link to a user login account', function () {
        actingAs($this->user);

        $loginUser = User::factory()->create();
        $employee = Employee::factory()->create(['user_id' => $loginUser->id]);

        expect($employee->user)->toBeInstanceOf(User::class)
            ->and($employee->user->id)->toBe($loginUser->id);
    });

    it('can be device-only with no linked user account', function () {
        actingAs($this->user);

        $employee = Employee::factory()->create(['user_id' => null]);

        expect($employee->user)->toBeNull();
    });

    it('can filter active employees', function () {
        actingAs($this->user);

        Employee::factory()->count(3)->create(['is_active' => true]);
        Employee::factory()->count(2)->inactive()->create();

        expect(Employee::where('is_active', true)->get())->toHaveCount(3)
            ->and(Employee::where('is_active', false)->get())->toHaveCount(2);
    });

    it('enforces a unique biometric pin', function () {
        actingAs($this->user);

        Employee::factory()->create(['biometric_pin' => '9999']);

        expect(fn () => Employee::factory()->create(['biometric_pin' => '9999']))
            ->toThrow(\Illuminate\Database\QueryException::class);
    });
});
