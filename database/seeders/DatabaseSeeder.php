<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // ── Core (existentes — no modificados) ─────────────────
            AdminUserSeeder::class,
            ProductSeeder::class,
            InterviewSeeder::class,
            PolicySeeder::class,
            LandingSeeder::class,
            MockScenarioSeeder::class,

            // ── Admin panel (nuevos) ────────────────────────────────
            RolePermissionSeeder::class,
            CartMatrixSeeder::class,
            MockInventorySeeder::class,
            MockAdminScenarioSeeder::class,
            MockShopifySyncSeeder::class,
        ]);
    }
}
