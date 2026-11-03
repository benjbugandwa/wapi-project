<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\NotificationChannelEnum;
use Illuminate\Support\Facades\Http;

class TwilioNotificationGateway
{
    public function send(NotificationChannelEnum $channel, string $to, string $message): bool
    {
        if (! in_array($channel, [NotificationChannelEnum::Sms, NotificationChannelEnum::WhatsApp], true)) {
            return false;
        }

        $accountSid = config('services.twilio.account_sid');
        $authToken = config('services.twilio.auth_token');
        $from = $channel === NotificationChannelEnum::WhatsApp
            ? config('services.twilio.whatsapp_from')
            : config('services.twilio.sms_from');

        if (! is_string($accountSid) || ! is_string($authToken) || ! is_string($from)) {
            return false;
        }

        $recipient = $channel === NotificationChannelEnum::WhatsApp && ! str_starts_with($to, 'whatsapp:')
            ? 'whatsapp:'.$to
            : $to;

        $response = Http::asForm()
            ->withBasicAuth($accountSid, $authToken)
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Messages.json", [
                'From' => $from,
                'To' => $recipient,
                'Body' => $message,
            ]);

        return $response->successful();
    }
}
