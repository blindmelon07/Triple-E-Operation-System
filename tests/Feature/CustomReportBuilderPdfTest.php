<?php

use App\Filament\Pages\CustomReportBuilder;
use App\Models\Sale;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

use function Pest\Laravel\actingAs;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('downloads the Custom Report Builder PDF via its Livewire action', function () {
    $user = User::factory()->create();
    Permission::firstOrCreate(['name' => 'View:CustomReportBuilder', 'guard_name' => 'web']);
    $user->givePermissionTo('View:CustomReportBuilder');
    actingAs($user);

    Sale::factory()->create(['date' => now(), 'total' => 500]);

    Livewire::test(CustomReportBuilder::class)
        ->set('module', 'sales')
        ->set('selectedColumns', ['id', 'date', 'customer.name', 'total'])
        ->call('generate')
        ->callAction('exportPdf')
        ->assertFileDownloaded();
});
