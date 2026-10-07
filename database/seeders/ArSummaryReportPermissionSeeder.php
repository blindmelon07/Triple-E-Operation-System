<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ArSummaryReportPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permission = Permission::firstOrCreate(['name' => 'View:ArSummaryReport', 'guard_name' => 'web']);

        // Whoever can already see the Aging Report gets the SOA Summary too.
        Role::where('name', 'super_admin')
            ->orWhereHas('permissions', fn ($q) => $q->where('name', 'View:AgingReport'))
            ->get()
            ->each(fn (Role $role) => $role->givePermissionTo($permission));
    }
}
