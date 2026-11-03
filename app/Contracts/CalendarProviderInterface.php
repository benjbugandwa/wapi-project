<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\CalendarIntegration;
use App\Models\Evenement;

interface CalendarProviderInterface
{
    /**
     * @return array{external_event_id?: string, payload?: array<string, mixed>}
     */
    public function sync(CalendarIntegration $integration, Evenement $evenement): array;

    public function cancel(CalendarIntegration $integration, Evenement $evenement): void;
}
