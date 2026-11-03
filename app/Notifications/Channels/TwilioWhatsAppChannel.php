<?php

declare(strict_types=1);

namespace App\Notifications\Channels;

use App\Enums\NotificationChannelEnum;
use App\Services\TwilioNotificationGateway;
use Illuminate\Notifications\Notification;

class TwilioWhatsAppChannel
{
    public function __construct(
        private readonly TwilioNotificationGateway $gateway,
    ) {}

    /**
     * Send the given notification.
     */
    public function send(object $notifiable, Notification $notification): bool
    {
        $to = $notifiable->routeNotificationFor('whatsapp', $notification)
            ?? $notifiable->routeNotificationFor('twilio_whatsapp', $notification)
            ?? $notifiable->phone_number
            ?? null;

        if (empty($to) || ! is_string($to)) {
            return false;
        }

        $message = null;

        if (method_exists($notification, 'toTwilioWhatsApp')) {
            $message = $notification->toTwilioWhatsApp($notifiable);
        } elseif (method_exists($notification, 'toWhatsApp')) {
            $message = $notification->toWhatsApp($notifiable);
        }

        if (empty($message) || ! is_string($message)) {
            return false;
        }

        return $this->gateway->send(NotificationChannelEnum::WhatsApp, $to, $message);
    }
}
