<?php

declare(strict_types=1);

namespace App\Enums;

enum RoleEnum: string
{
    case SystemAdmin = 'system_admin';
    case Admin = 'admin';
    case Avocat = 'avocat';
    case Secretaire = 'secretaire';
    case Comptable = 'comptable';
    case Stagiaire = 'stagiaire';

    public function label(): string
    {
        return match ($this) {
            self::SystemAdmin => 'Administrateur système',
            self::Admin => 'Administrateur cabinet',
            self::Avocat => 'Avocat',
            self::Secretaire => 'Secrétaire juridique',
            self::Comptable => 'Comptable',
            self::Stagiaire => 'Stagiaire',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(
            static fn (self $role): string => $role->value,
            self::cases(),
        );
    }
}
