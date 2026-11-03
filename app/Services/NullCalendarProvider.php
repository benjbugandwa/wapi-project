<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\CalendarProviderInterface;
use App\Models\CalendarIntegration;
use App\Models\Evenement;

class NullCalendarProvider implements CalendarProviderInterface
{
    /**
     * @return array{external_event_id?: string, payload?: array<string, mixed>}
     */
    public function sync(CalendarIntegration $integration, Evenement $evenement): array
    {
        return [
            'external_event_id' => $integration->provider->value.'-'.$evenement->id,
            'payload' => ['driver' => 'null'],
        ];
    }

    public function cancel(CalendarIntegration $integration, Evenement $evenement): void
    {
        //
    }
}
