<?php

declare(strict_types=1);

namespace App\Enums;

enum FactureStatusEnum: string
{
    case Brouillon = 'brouillon';
    case Envoyee = 'envoyee';
    case PayeePartiellement = 'payee_partiellement';
    case Soldee = 'soldee';
    case Annulee = 'annulee';

    public function label(): string
    {
        return match ($this) {
            self::Brouillon => 'Brouillon',
            self::Envoyee => 'Envoyee',
            self::PayeePartiellement => 'Payee partiellement',
            self::Soldee => 'Soldee',
            self::Annulee => 'Annulee',
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
