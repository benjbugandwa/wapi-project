<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Cabinet;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CabinetInvitationNotification extends Notification
{
    public function __construct(
        private readonly string $token,
        private readonly Cabinet $cabinet,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Invitation a rejoindre :cabinet', ['cabinet' => $this->cabinet->denomination]))
            ->greeting(__('Bonjour,'))
            ->line(__('Vous avez ete invite a rejoindre le cabinet :cabinet sur Wapi.', [
                'cabinet' => $this->cabinet->denomination,
            ]))
            ->action(__('Accepter l invitation'), route('cabinet-invitations.accept', $this->token))
            ->line(__('Si vous n etes pas concerne par cette invitation, vous pouvez ignorer cet email.'));
    }
}
