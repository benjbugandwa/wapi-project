<?php

declare(strict_types=1);

namespace App\Enums;

enum MethodePaiementEnum: string
{
    case Virement = 'virement';
    case Cheque = 'cheque';
    case Especes = 'especes';
    case Carte = 'carte';
    case MobileMoney = 'mobile_money';
    case Autre = 'autre';

    public function label(): string
    {
        return match ($this) {
            self::Virement => 'Virement',
            self::Cheque => 'Cheque',
            self::Especes => 'Especes',
            self::Carte => 'Carte',
            self::MobileMoney => 'Mobile money',
            self::Autre => 'Autre',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(
            static fn (self $method): string => $method->value,
            self::cases(),
        );
    }
}
