<?php

declare(strict_types=1);

namespace App\Enums;

enum NotificationChannelEnum: string
{
    case Email = 'email';
    case Sms = 'sms';
    case InApp = 'in_app';
    case Push = 'push';
    case WhatsApp = 'whatsapp';

    public function label(): string
    {
        return match ($this) {
            self::Email => 'Email',
            self::Sms => 'SMS',
            self::InApp => 'Application',
            self::Push => 'Push',
            self::WhatsApp => 'WhatsApp',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(
            static fn (self $channel): string => $channel->value,
            self::cases(),
        );
    }
}
