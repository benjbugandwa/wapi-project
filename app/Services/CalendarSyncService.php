<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CalendarSyncStatusEnum;
use App\Models\CalendarIntegration;
use App\Models\CalendarSyncLog;
use App\Models\Evenement;
use Throwable;

class CalendarSyncService
{
    public function __construct(
        private readonly NullCalendarProvider $calendarProvider,
    ) {}

    public function syncForEvent(Evenement $evenement): void
    {
        $evenement->loadMissing('participants.calendarIntegrations');

        foreach ($evenement->participants as $participant) {
            foreach ($participant->calendarIntegrations->where('is_active', true) as $integration) {
                $this->sync($integration, $evenement);
            }
        }
    }

    public function cancelForEvent(Evenement $evenement): void
    {
        $evenement->loadMissing('participants.calendarIntegrations');

        foreach ($evenement->participants as $participant) {
            foreach ($participant->calendarIntegrations->where('is_active', true) as $integration) {
                $this->cancel($integration, $evenement);
            }
        }
    }

    private function sync(CalendarIntegration $integration, Evenement $evenement): void
    {
        try {
            $result = $this->calendarProvider->sync($integration, $evenement);

            CalendarSyncLog::query()->create([
                'cabinet_id' => $evenement->cabinet_id,
                'calendar_integration_id' => $integration->id,
                'evenement_id' => $evenement->id,
                'provider' => $integration->provider,
                'external_event_id' => $result['external_event_id'] ?? null,
                'sync_status' => CalendarSyncStatusEnum::Synced,
                'payload' => $result['payload'] ?? [],
            ]);

            $integration->forceFill([
                'last_synced_at' => now(),
                'last_error' => null,
            ])->save();
        } catch (Throwable $exception) {
            $this->recordFailure($integration, $evenement, $exception);
        }
    }

    private function cancel(CalendarIntegration $integration, Evenement $evenement): void
    {
        try {
            $this->calendarProvider->cancel($integration, $evenement);

            CalendarSyncLog::query()->create([
                'cabinet_id' => $evenement->cabinet_id,
                'calendar_integration_id' => $integration->id,
                'evenement_id' => $evenement->id,
                'provider' => $integration->provider,
                'sync_status' => CalendarSyncStatusEnum::Synced,
                'payload' => ['action' => 'cancel'],
            ]);
        } catch (Throwable $exception) {
            $this->recordFailure($integration, $evenement, $exception);
        }
    }

    private function recordFailure(CalendarIntegration $integration, Evenement $evenement, Throwable $exception): void
    {
        CalendarSyncLog::query()->create([
            'cabinet_id' => $evenement->cabinet_id,
            'calendar_integration_id' => $integration->id,
            'evenement_id' => $evenement->id,
            'provider' => $integration->provider,
            'sync_status' => CalendarSyncStatusEnum::Failed,
            'error_message' => $exception->getMessage(),
        ]);

        $integration->forceFill([
            'last_error' => $exception->getMessage(),
        ])->save();
    }
}
