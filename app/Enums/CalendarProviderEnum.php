<?php

declare(strict_types=1);

namespace App\Enums;

enum CalendarProviderEnum: string
{
    case Google = 'google';
    case Outlook = 'outlook';

    public function label(): string
    {
        return match ($this) {
            self::Google => 'Google Calendar',
            self::Outlook => 'Microsoft Outlook',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(
            static fn (self $provider): string => $provider->value,
            self::cases(),
        );
    }
}
