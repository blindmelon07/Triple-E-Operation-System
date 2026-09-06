<?php

use App\Filament\Pages\CustomReportBuilder;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

use function Pest\Laravel\actingAs;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('generates a report even when underlying text has invalid UTF-8 bytes', function () {
    $user = User::factory()->create();
    Permission::firstOrCreate(['name' => 'View:CustomReportBuilder', 'guard_name' => 'web']);
    $user->givePermissionTo('View:CustomReportBuilder');
    actingAs($user);

    // Simulate legacy/pasted-in text with an invalid UTF-8 byte sequence,
    // the same kind of data Utf8::clean() exists elsewhere in this app to handle.
    $customer = Customer::factory()->create();
    \Illuminate\Support\Facades\DB::table('customers')->where('id', $customer->id)->update([
        'name' => "Bad Name \xB1\xFE Corp",
    ]);

    Sale::factory()->create(['date' => now(), 'total' => 500, 'customer_id' => $customer->id]);

    Livewire::test(CustomReportBuilder::class)
        ->set('module', 'sales')
        ->set('selectedColumns', ['id', 'date', 'customer.name', 'total'])
        ->call('generate')
        ->assertHasNoErrors();
});
