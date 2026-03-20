<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RecordPaymentPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'RecordPaymentSale',
            'RecordPaymentPurchase',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        // Grant to roles that should be able to record payments
        $grantTo = ['super_admin', 'admin', 'cashier'];

        foreach ($grantTo as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $role->givePermissionTo($permissions);
            }
        }
    }
}
