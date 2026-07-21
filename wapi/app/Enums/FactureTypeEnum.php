<?php

declare(strict_types=1);

namespace App\Enums;

enum FactureTypeEnum: string
{
    case Provision = 'provision';
    case Definitive = 'definitive';
    case Avoir = 'avoir';

    public function label(): string
    {
        return match ($this) {
            self::Provision => 'Provision',
            self::Definitive => 'Definitive',
            self::Avoir => 'Avoir',
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
