<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create roles
        $adminRole = Role::create(['name' => 'admin']);
        $userRole = Role::create(['name' => 'user']);
        $staffRole = Role::create(['name' => 'staff']);

        // Create permissions
        $permissions = [
            'view complaints',
            'create complaints',
            'edit complaints',
            'delete complaints',
            'manage users',
            'manage categories',
            'manage departments',
            'view reports',
            'export reports',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Assign permissions to roles
        $adminRole->givePermissionTo(Permission::all());
        
        $staffRole->givePermissionTo([
            'view complaints',
            'create complaints',
            'edit complaints',
            'view reports',
        ]);
        
        $userRole->givePermissionTo([
            'view complaints',
            'create complaints',
        ]);
    }
}
