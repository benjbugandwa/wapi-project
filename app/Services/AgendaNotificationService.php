<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\NotificationChannelEnum;
use App\Models\Evenement;
use App\Models\User;
use App\Notifications\AgendaEventNotification;

class AgendaNotificationService
{
    public function __construct(
        private readonly NotificationPreferenceService $preferenceService,
        private readonly TwilioNotificationGateway $twilioGateway,
        private readonly AuditLogService $auditLogService,
    ) {}

    public function notifyParticipants(Evenement $evenement, string $action): void
    {
        $evenement->loadMissing('participants.notificationPreferences', 'cabinet');

        foreach ($evenement->participants as $participant) {
            $this->send($participant, $evenement, NotificationChannelEnum::Email, $action);
            $this->send($participant, $evenement, NotificationChannelEnum::Sms, $action);
            $this->send($participant, $evenement, NotificationChannelEnum::WhatsApp, $action);
            $this->send($participant, $evenement, NotificationChannelEnum::InApp, $action);
        }
    }

    public function send(User $user, Evenement $evenement, NotificationChannelEnum $channel, string $action): bool
    {
        $user->loadMissing('notificationPreferences');

        if (! $this->preferenceService->enabled($user, $channel)) {
            return false;
        }

        if ($channel === NotificationChannelEnum::Email) {
            $user->notify(new AgendaEventNotification($evenement, $action));

            return true;
        }

        if (in_array($channel, [NotificationChannelEnum::Sms, NotificationChannelEnum::WhatsApp], true)) {
            $destination = $this->preferenceService->destination($user, $channel);

            if ($destination === null || $destination === '') {
                return false;
            }

            return $this->twilioGateway->send(
                channel: $channel,
                to: $destination,
                message: $this->message($evenement, $action),
            );
        }

        $this->auditLogService->record(
            action: 'agenda.notification.in_app',
            user: $user,
            cabinet: $evenement->cabinet,
            auditable: $evenement,
            metadata: ['action' => $action],
        );

        return true;
    }

    private function message(Evenement $evenement, string $action): string
    {
        return __('Agenda : :action - :titre le :date', [
            'action' => $action,
            'titre' => $evenement->titre,
            'date' => $evenement->date_debut->format('Y-m-d H:i'),
        ]);
    }
}
