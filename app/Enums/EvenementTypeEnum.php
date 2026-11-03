<?php

declare(strict_types=1);

namespace App\Enums;

enum EvenementTypeEnum: string
{
    case Audience = 'audience';
    case RendezVous = 'rendez_vous';
    case DateButoir = 'date_butoir';
    case ReunionInterne = 'reunion_interne';

    public function label(): string
    {
        return match ($this) {
            self::Audience => 'Audience',
            self::RendezVous => 'Rendez-vous',
            self::DateButoir => 'Date butoir',
            self::ReunionInterne => 'Reunion interne',
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
