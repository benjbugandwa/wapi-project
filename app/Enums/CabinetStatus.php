<?php

declare(strict_types=1);

namespace App\Enums;

enum CabinetStatus: string
{
    case Active = 'actif';
    case Suspended = 'suspendu';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Actif',
            self::Suspended => 'Suspendu',
        };
    }
}
