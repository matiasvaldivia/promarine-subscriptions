<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // ── Permisos ────────────────────────────────────────────────
        $permissionDefs = [
            'manage_users'         => 'Gestionar usuarios administrativos',
            'manage_customers'     => 'Gestionar clientes',
            'manage_products'      => 'Gestionar productos y variantes',
            'manage_inventory'     => 'Gestionar inventario y stock',
            'manage_orders'        => 'Gestionar pedidos y estados',
            'manage_subscriptions' => 'Gestionar suscripciones',
            'manage_policies'      => 'Gestionar políticas y textos legales',
            'run_sync'             => 'Ejecutar sincronizaciones Shopify mock',
            'view_audit'           => 'Ver logs de auditoría',
        ];

        $permissions = [];
        foreach ($permissionDefs as $name => $label) {
            $permissions[$name] = Permission::updateOrCreate(
                ['name' => $name],
                ['label' => $label]
            );
        }

        // ── Roles y asignación de permisos ────────────────────────
        $roleDefs = [
            'super_admin' => array_keys($permissionDefs),
            'decision_owner' => [
                'manage_customers',
                'manage_products',
                'manage_inventory',
                'manage_orders',
                'manage_subscriptions',
                'manage_policies',
                'run_sync',
                'view_audit',
            ],
            'operations' => [
                'manage_customers',
                'manage_orders',
                'manage_inventory',
                'run_sync',
            ],
            'customer_support' => [
                'manage_customers',
                'manage_subscriptions',
            ],
            'logistics' => [
                'manage_orders',
                'manage_inventory',
            ],
            'read_only' => [
                'view_audit',
            ],
        ];

        foreach ($roleDefs as $roleName => $rolePermissions) {
            $role = Role::updateOrCreate(['name' => $roleName]);
            $permissionIds = collect($rolePermissions)
                ->map(fn ($p) => $permissions[$p]->id)
                ->toArray();
            $role->permissions()->sync($permissionIds);
        }

        // ── Asignar rol decision_owner a Tamara (crear si no existe) ──
        $tamara = User::firstOrCreate(
            ['username' => 'tamara'],
            [
                'name'     => 'Tamara',
                'email'    => 'tamara@promarine.com.ar',
                'password' => 'demo-secret',
            ]
        );
        $decisionOwner = Role::where('name', 'decision_owner')->first();
        if ($decisionOwner && ! $tamara->hasRole('decision_owner')) {
            $tamara->roles()->attach($decisionOwner->id);
        }

        // ── Asignar rol super_admin al primer usuario admin ───────
        $superAdmin = User::firstOrCreate(
            ['username' => 'admin'],
            [
                'name'     => 'Admin',
                'email'    => 'admin@promarine.com.ar',
                'password' => 'admin-secret',
            ]
        );
        $superAdminRole = Role::where('name', 'super_admin')->first();
        if ($superAdminRole && ! $superAdmin->hasRole('super_admin')) {
            $superAdmin->roles()->syncWithoutDetaching([$superAdminRole->id]);
        }

        $this->command->info('✓ Roles y permisos configurados (6 roles, 9 permisos)');
    }
}
