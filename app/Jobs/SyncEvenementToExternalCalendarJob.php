<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Evenement;
use App\Services\CalendarSyncService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncEvenementToExternalCalendarJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Evenement $evenement,
    ) {}

    public function handle(CalendarSyncService $calendarSyncService): void
    {
        $calendarSyncService->syncForEvent($this->evenement);
    }
}
