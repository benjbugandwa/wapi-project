<?php

declare(strict_types=1);

namespace App\Notifications\Channels;

use App\Enums\NotificationChannelEnum;
use App\Services\TwilioNotificationGateway;
use Illuminate\Notifications\Notification;

class TwilioSmsChannel
{
    public function __construct(
        private readonly TwilioNotificationGateway $gateway,
    ) {}

    /**
     * Send the given notification.
     */
    public function send(object $notifiable, Notification $notification): bool
    {
        $to = $notifiable->routeNotificationFor('sms', $notification)
            ?? $notifiable->routeNotificationFor('twilio_sms', $notification)
            ?? $notifiable->phone_number
            ?? null;

        if (empty($to) || ! is_string($to)) {
            return false;
        }

        $message = null;

        if (method_exists($notification, 'toTwilioSms')) {
            $message = $notification->toTwilioSms($notifiable);
        } elseif (method_exists($notification, 'toSms')) {
            $message = $notification->toSms($notifiable);
        }

        if (empty($message) || ! is_string($message)) {
            return false;
        }

        return $this->gateway->send(NotificationChannelEnum::Sms, $to, $message);
    }
}
