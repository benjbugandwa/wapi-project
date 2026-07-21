<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Evenement;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AgendaEventNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Evenement $evenement,
        private readonly string $action,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Agenda - :action', ['action' => $this->action]))
            ->greeting(__('Bonjour,'))
            ->line(__('Evenement : :titre', ['titre' => $this->evenement->titre]))
            ->line(__('Debut : :date', ['date' => $this->evenement->date_debut->format('Y-m-d H:i')]))
            ->line(__('Fin : :date', ['date' => $this->evenement->date_fin->format('Y-m-d H:i')]))
            ->line(__('Lieu : :lieu', ['lieu' => $this->evenement->lieu ?? '-']));
    }
}
