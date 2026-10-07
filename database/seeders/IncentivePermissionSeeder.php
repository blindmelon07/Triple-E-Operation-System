<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class IncentivePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = collect(['ViewAny', 'View', 'Create', 'Update', 'Delete'])
            ->map(fn ($action) => Permission::firstOrCreate(['name' => "{$action}:Incentive", 'guard_name' => 'web']));

        // Whoever manages cash advances manages incentives too.
        Role::where('name', 'super_admin')
            ->orWhereHas('permissions', fn ($q) => $q->where('name', 'ViewAny:CashAdvance'))
            ->get()
            ->each(fn (Role $role) => $role->givePermissionTo($permissions));
    }
}
