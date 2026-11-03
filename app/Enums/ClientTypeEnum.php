<?php

declare(strict_types=1);

namespace App\Enums;

enum ClientTypeEnum: string
{
    case PersonnePhysique = 'personne_physique';
    case PersonneMorale = 'personne_morale';

    public function label(): string
    {
        return match ($this) {
            self::PersonnePhysique => 'Personne physique',
            self::PersonneMorale => 'Personne morale',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(
            static fn (self $type): string => $type->value,
            self::cases(),
        );
    }
}
