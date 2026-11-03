<?php

declare(strict_types=1);

namespace App\Enums;

enum EvenementStatusEnum: string
{
    case Planifie = 'planifie';
    case Modifie = 'modifie';
    case Annule = 'annule';

    public function label(): string
    {
        return match ($this) {
            self::Planifie => 'Planifie',
            self::Modifie => 'Modifie',
            self::Annule => 'Annule',
        };
    }
}
