<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\NotificationChannelEnum;
use App\Enums\NotificationProviderEnum;
use App\Models\NotificationWebhookEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Twilio\Security\RequestValidator;

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
        $shouldValidate = (bool) config('services.twilio.validate_webhook_signature', false);

        if (! $shouldValidate) {
            return true;
        }

        $authToken = config('services.twilio.auth_token');
        $signature = $request->header('X-Twilio-Signature');

        if (! is_string($authToken) || $authToken === '' || ! is_string($signature) || $signature === '') {
            return false;
        }

        $url = $this->resolveTwilioWebhookUrl($request);
        $validator = new RequestValidator($authToken);

        /** @var array<string, mixed> $postData */
        $postData = $request->post();

        return $validator->validate($signature, $url, $postData);
    }

    private function resolveTwilioWebhookUrl(Request $request): string
    {
        $url = $request->fullUrl();

        // Gestion reverse proxy Railway : forcer https si X-Forwarded-Proto vaut https
        $proto = $request->header('X-Forwarded-Proto');
        if ($proto === 'https' && str_starts_with($url, 'http://')) {
            $url = 'https://'.substr($url, 7);
        }

        // Si X-Forwarded-Host est renseigne par le proxy Railway, aligner l'hote de l'URL
        $host = $request->header('X-Forwarded-Host');
        if (is_string($host) && $host !== '') {
            $parsed = parse_url($url);
            if ($parsed !== false && isset($parsed['host']) && $parsed['host'] !== $host) {
                $scheme = $parsed['scheme'] ?? ($proto ?: 'https');
                $path = $parsed['path'] ?? '';
                $query = isset($parsed['query']) ? '?'.$parsed['query'] : '';
                $url = "{$scheme}://{$host}{$path}{$query}";
            }
        }

        return $url;
    }
}
