<?php

declare(strict_types=1);

namespace App\Enums;

enum TypeFacturationEnum: string
{
    case Forfait = 'forfait';
    case TauxHoraire = 'taux_horaire';
    case HonoraireResultat = 'honoraire_resultat';
    case AideJuridictionnelle = 'aide_juridictionnelle';

    public function label(): string
    {
        return match ($this) {
            self::Forfait => 'Forfait',
            self::TauxHoraire => 'Taux horaire',
            self::HonoraireResultat => 'Honoraire de résultat',
            self::AideJuridictionnelle => 'Aide juridictionnelle',
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
