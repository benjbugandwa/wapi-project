<?php

declare(strict_types=1);

namespace App\Enums;

enum DossierStatusEnum: string
{
    case Ouvert = 'ouvert';
    case EnCours = 'en_cours';
    case EnAttente = 'en_attente';
    case Plaide = 'plaide';
    case Clos = 'clos';
    case Archive = 'archive';

    public function label(): string
    {
        return match ($this) {
            self::Ouvert => 'Ouvert',
            self::EnCours => 'En cours',
            self::EnAttente => 'En attente',
            self::Plaide => 'Plaidé',
            self::Clos => 'Clos',
            self::Archive => 'Archivé',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(
            static fn (self $status): string => $status->value,
            self::cases(),
        );
    }
}
