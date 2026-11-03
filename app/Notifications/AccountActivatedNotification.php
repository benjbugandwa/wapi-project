<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Notifications\Channels\TwilioSmsChannel;
use App\Notifications\Channels\TwilioWhatsAppChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountActivatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @return array<int, string|class-string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['mail'];

        if (! empty($notifiable->phone_number)) {
            $channels[] = TwilioSmsChannel::class;
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Votre compte Wapi a été activé'))
            ->greeting(__('Bonjour :nom,', ['nom' => $notifiable->nom ?? '']))
            ->line(__('Bonne nouvelle ! Votre compte utilisateur sur la plateforme Wapi a été activé par un administrateur.'))
            ->action(__('Se connecter à Wapi'), route('login'))
            ->line(__('Vous pouvez désormais vous connecter et accéder aux dossiers et fonctionnalités de votre cabinet.'));
    }

    public function toTwilioSms(object $notifiable): string
    {
        return __('Wapi : Votre compte a été activé par un administrateur. Vous pouvez maintenant vous connecter sur :url', [
            'url' => route('login'),
        ]);
    }

    public function toTwilioWhatsApp(object $notifiable): string
    {
        return __('Bonjour ! Votre compte Wapi a été validé et activé par un administrateur. Rendez-vous sur :url pour vous connecter.', [
            'url' => route('login'),
        ]);
    }
}
