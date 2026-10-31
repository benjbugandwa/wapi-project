<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\CabinetStatus;
use App\Enums\RoleEnum;
use App\Models\Cabinet;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);
        $this->call(DossierAccessUserSeeder::class);

        /** @var Cabinet $cabinet */
        $cabinet = Cabinet::withTrashed()->firstOrNew([
            'email_repondant' => 'contact@example.com',
        ]);

        $cabinet->forceFill([
            'denomination' => 'Cabinet Demo',
            'adresse_cabinet' => '1 Avenue de la Justice',
            'nom_repondant' => 'Admin Repondant',
            'telephone_repondant' => '+243000000000',
            'description' => 'Cabinet principal de demonstration',
            'statut_cabinet' => CabinetStatus::Active,
            'deleted_at' => null,
        ])->save();

        /** @var User $admin */
        $admin = User::withTrashed()->firstOrNew([
            'email' => 'admin@example.com',
        ]);

        $admin->forceFill([
            'cabinet_id' => $cabinet->id,
            'nom' => 'Admin Demo',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'is_active' => true,
            'deleted_at' => null,
        ])->save();

        $admin->syncRoles([RoleEnum::Admin->value]);

        /** @var User $sysadmin */
        $sysadmin = User::withTrashed()->firstOrNew([
            'email' => 'sysadmin@example.com',
        ]);

        $sysadmin->forceFill([
            'nom' => 'Admin Systeme Demo',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'is_active' => true,
            'deleted_at' => null,
        ])->save();

        $sysadmin->syncRoles([RoleEnum::SystemAdmin->value]);
    }
}
