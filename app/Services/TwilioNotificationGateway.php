<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\NotificationChannelEnum;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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

        if (! is_string($accountSid) || ! is_string($authToken) || ! is_string($from) || empty($accountSid) || empty($authToken) || empty($from)) {
            Log::warning('Twilio credentials missing or incomplete', [
                'channel' => $channel->value,
                'has_sid' => ! empty($accountSid),
                'has_token' => ! empty($authToken),
                'has_from' => ! empty($from),
            ]);

            return false;
        }

        // Nettoyer les espaces dans le numéro de destination
        $cleanTo = preg_replace('/\s+/', '', $to) ?? $to;

        // Préfixer avec whatsapp: pour WhatsApp si non présent
        if ($channel === NotificationChannelEnum::WhatsApp) {
            if (! str_starts_with($from, 'whatsapp:')) {
                $from = 'whatsapp:'.$from;
            }
            $recipient = str_starts_with($cleanTo, 'whatsapp:') ? $cleanTo : 'whatsapp:'.$cleanTo;
        } else {
            $recipient = $cleanTo;
        }

        try {
            $response = Http::asForm()
                ->withBasicAuth($accountSid, $authToken)
                ->timeout(15)
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Messages.json", [
                    'From' => $from,
                    'To' => $recipient,
                    'Body' => $message,
                ]);

            if (! $response->successful()) {
                Log::warning('Twilio dispatch failed', [
                    'channel' => $channel->value,
                    'recipient' => $recipient,
                    'status' => $response->status(),
                    'error' => $response->json(),
                ]);

                return false;
            }

            Log::info('Twilio notification sent successfully', [
                'channel' => $channel->value,
                'recipient' => $recipient,
                'sid' => $response->json('sid'),
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::error('Twilio notification exception', [
                'channel' => $channel->value,
                'recipient' => $recipient,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
