<?php

declare(strict_types=1);

namespace App\Enums;

enum DossierUserRoleEnum: string
{
    case Responsable = 'responsable';
    case Collaborateur = 'collaborateur';
    case Observateur = 'observateur';

    public function label(): string
    {
        return match ($this) {
            self::Responsable => 'Responsable',
            self::Collaborateur => 'Collaborateur',
            self::Observateur => 'Observateur',
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
