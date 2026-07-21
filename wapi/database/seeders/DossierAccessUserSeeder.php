<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\CabinetStatus;
use App\Enums\RoleEnum;
use App\Models\Cabinet;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DossierAccessUserSeeder extends Seeder
{
    public const CABINET_NAME = 'Cabinet Dossiers Demo';

    public const USER_EMAIL = 'avocat.dossiers@example.com';

    public const USER_PASSWORD = 'password';

    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);

        $cabinet = $this->activeCabinet();
        $user = $this->verifiedActiveUser($cabinet);

        $user->syncRoles([RoleEnum::Avocat->value]);
    }

    private function activeCabinet(): Cabinet
    {
        /** @var Cabinet $cabinet */
        $cabinet = Cabinet::withTrashed()->firstOrNew([
            'denomination' => self::CABINET_NAME,
        ]);

        $cabinet->forceFill([
            'adresse_cabinet' => '1 Avenue de la Justice',
            'description' => 'Cabinet de demonstration pour tester le module Dossiers.',
            'nom_repondant' => 'Responsable Dossiers',
            'telephone_repondant' => '+243000000000',
            'email_repondant' => 'contact.dossiers@example.com',
            'statut_cabinet' => CabinetStatus::Active,
            'logo_cabinet' => null,
            'deleted_at' => null,
        ])->save();

        return $cabinet;
    }

    private function verifiedActiveUser(Cabinet $cabinet): User
    {
        /** @var User $user */
        $user = User::withTrashed()->firstOrNew([
            'email' => self::USER_EMAIL,
        ]);

        $user->forceFill([
            'cabinet_id' => $cabinet->id,
            'nom' => 'Avocat Dossiers Demo',
            'password' => Hash::make(self::USER_PASSWORD),
            'phone_number' => '+243000000001',
            'profile_photo_path' => null,
            'email_verified_at' => now(),
            'is_active' => true,
            'deleted_at' => null,
        ])->save();

        return $user;
    }
}
