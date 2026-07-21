<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\NotificationChannelEnum;
use App\Enums\NotificationProviderEnum;
use App\Models\NotificationWebhookEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TwilioWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        if (! $this->signatureIsValid($request)) {
            return response()->json([
                'message' => __('Signature webhook invalide.'),
            ], Response::HTTP_FORBIDDEN);
        }

        $from = $request->string('From')->toString();
        $channel = str_starts_with($from, 'whatsapp:')
            ? NotificationChannelEnum::WhatsApp
            : NotificationChannelEnum::Sms;

        NotificationWebhookEvent::query()->create([
            'provider' => NotificationProviderEnum::Twilio,
            'channel' => $channel,
            'external_message_id' => $request->string('MessageSid')->toString() ?: null,
            'from' => $from ?: null,
            'to' => $request->string('To')->toString() ?: null,
            'body' => $request->string('Body')->toString() ?: null,
            'payload' => $request->all(),
            'processed_at' => now(),
        ]);

        return response()->json([
            'data' => [],
            'message' => __('Message Twilio recu.'),
        ]);
    }

    private function signatureIsValid(Request $request): bool
    {
        if (! config('services.twilio.validate_webhook_signature', false)) {
            return true;
        }

        $authToken = config('services.twilio.auth_token');
        $signature = $request->header('X-Twilio-Signature');

        if (! is_string($authToken) || ! is_string($signature)) {
            return false;
        }

        $data = $request->request->all();
        ksort($data);

        $payload = $request->fullUrl();

        foreach ($data as $key => $value) {
            $payload .= $key.$value;
        }

        $expected = base64_encode(hash_hmac('sha1', $payload, $authToken, true));

        return hash_equals($expected, $signature);
    }
}
