<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\RoleEnum;
use App\Models\Cabinet;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);
        $this->call(DossierAccessUserSeeder::class);

        $cabinet = Cabinet::factory()->create([
            'denomination' => 'Cabinet Demo',
            'email_repondant' => 'contact@example.com',
        ]);

        $cabinet->users()->create([
            'nom' => 'Admin Demo',
            'email' => 'admin@example.com',
            'password' => 'password',
            'email_verified_at' => now(),
            'is_active' => true,
        ])->assignRole(RoleEnum::Admin->value);

        User::factory()->systemAdmin()->create([
            'nom' => 'Admin Systeme Demo',
            'email' => 'sysadmin@example.com',
            'password' => 'password',
            'email_verified_at' => now(),
            'is_active' => true,
        ])->assignRole(RoleEnum::SystemAdmin->value);
    }
}
