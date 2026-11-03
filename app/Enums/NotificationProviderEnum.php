<?php

declare(strict_types=1);

namespace App\Enums;

enum NotificationProviderEnum: string
{
    case Twilio = 'twilio';
}
