<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Rappel;
use App\Services\AgendaNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendRappelNotificationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Rappel $rappel,
    ) {}

    public function handle(AgendaNotificationService $notificationService): void
    {
        $this->rappel->loadMissing(['evenement', 'user']);

        if ($this->rappel->est_envoye || $this->rappel->evenement->trashed()) {
            return;
        }

        $sent = $notificationService->send(
            user: $this->rappel->user,
            evenement: $this->rappel->evenement,
            channel: $this->rappel->canal_notification,
            action: 'rappel',
        );

        if (! $sent) {
            return;
        }

        $this->rappel->forceFill([
            'est_envoye' => true,
            'sent_at' => now(),
        ])->save();
    }
}
