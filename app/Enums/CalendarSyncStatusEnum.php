<?php

declare(strict_types=1);

namespace App\Enums;

enum CalendarSyncStatusEnum: string
{
    case Pending = 'pending';
    case Synced = 'synced';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente',
            self::Synced => 'Synchronise',
            self::Failed => 'Echec',
        };
    }
}
