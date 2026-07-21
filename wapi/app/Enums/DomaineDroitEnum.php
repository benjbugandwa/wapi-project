<?php

declare(strict_types=1);

namespace App\Enums;

enum DomaineDroitEnum: string
{
    case Penal = 'penal';
    case Civil = 'civil';
    case Social = 'social';
    case Autre = 'autre';

    public function label(): string
    {
        return match ($this) {
            self::Penal => 'Pénal',
            self::Civil => 'Civil',
            self::Social => 'Social',
            self::Autre => 'Autre',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(
            static fn (self $domaine): string => $domaine->value,
            self::cases(),
        );
    }
}
