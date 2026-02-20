<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        // Super Admin (sem tenant)
        User::create([
            'name' => 'Super Admin',
            'email' => 'admin@plataforma360.com',
            'password' => Hash::make('password'),
            'role' => 'super_admin',
            'is_super_admin' => true,
            'tenant_id' => null,
        ]);

        // Tenant de demonstração
        $tenant = Tenant::create([
            'name' => 'Empresa Demo',
            'slug' => 'demo',
            'plan' => 'pro',
            'status' => 'active',
            'max_users' => 10,
            'max_leads' => 5000,
            'max_pipelines' => 10,
            'max_custom_fields' => 50,
            'api_rate_limit' => 10000,
        ]);

        // Admin do tenant demo
        User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Admin Demo',
            'email' => 'admin@demo.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'is_super_admin' => false,
        ]);

        // Gestor do tenant demo
        User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Gestor Demo',
            'email' => 'gestor@demo.com',
            'password' => Hash::make('password'),
            'role' => 'manager',
            'is_super_admin' => false,
        ]);
    }
}
