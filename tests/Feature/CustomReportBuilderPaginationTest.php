<?php

use App\Filament\Pages\CustomReportBuilder;
use App\Models\Customer;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

use function Pest\Laravel\actingAs;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('paginates the on-screen report preview', function () {
    $user = User::factory()->create();
    Permission::firstOrCreate(['name' => 'View:CustomReportBuilder', 'guard_name' => 'web']);
    $user->givePermissionTo('View:CustomReportBuilder');
    actingAs($user);

    Customer::factory()->count(30)->create();

    $component = Livewire::test(CustomReportBuilder::class)
        ->set('module', 'customers')
        ->set('selectedColumns', ['id', 'name'])
        ->set('perPage', 10)
        ->call('generate');

    expect($component->get('resultCount'))->toBe(30);
    expect($component->instance()->lastPage())->toBe(3);
    expect($component->instance()->paginatedRows())->toHaveCount(10);

    $component->call('nextPage');
    expect($component->get('page'))->toBe(2);

    $component->call('nextPage')->call('nextPage'); // clamps at the last page
    expect($component->get('page'))->toBe(3);
    expect($component->instance()->paginatedRows())->toHaveCount(10);

    $component->call('previousPage');
    expect($component->get('page'))->toBe(2);

    // Changing the page size resets back to page 1.
    $component->set('perPage', 25);
    expect($component->get('page'))->toBe(1);
    expect($component->instance()->lastPage())->toBe(2);
});
