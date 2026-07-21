<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class SaveNotificationPreferencesData
{
    /**
     * @param  array<string, bool>  $enabledChannels
     * @param  array<string, string|null>  $destinations
     */
    public function __construct(
        public array $enabledChannels,
        public array $destinations = [],
    ) {}
}
