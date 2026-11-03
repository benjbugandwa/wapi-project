<?php

declare(strict_types=1);

namespace Tests\Feature\Agenda;

use App\Models\NotificationWebhookEvent;
use Illuminate\Support\Facades\Config;
use Twilio\Security\RequestValidator;

test('twilio webhook rejects request when signature validation is enabled and signature is missing', function () {
    Config::set('services.twilio.validate_webhook_signature', true);
    Config::set('services.twilio.auth_token', 'secret-twilio-token');

    $response = $this->postJson(route('webhooks.twilio.messages'), [
        'From' => '+243810000100',
        'To' => '+243810000000',
        'Body' => 'Hello test',
        'MessageSid' => 'SM123456789',
    ]);

    $response->assertForbidden()
        ->assertJson(['message' => 'Signature webhook invalide.']);
});

test('twilio webhook rejects request when signature is invalid', function () {
    Config::set('services.twilio.validate_webhook_signature', true);
    Config::set('services.twilio.auth_token', 'secret-twilio-token');

    $response = $this->withHeaders([
        'X-Twilio-Signature' => 'invalid-signature-hash',
    ])->postJson(route('webhooks.twilio.messages'), [
        'From' => '+243810000100',
        'To' => '+243810000000',
        'Body' => 'Hello test',
        'MessageSid' => 'SM123456789',
    ]);

    $response->assertForbidden();
});

test('twilio webhook accepts request when signature is valid via twilio sdk validator', function () {
    $token = 'test-token-xyz-123456';
    Config::set('services.twilio.validate_webhook_signature', true);
    Config::set('services.twilio.auth_token', $token);

    $url = route('webhooks.twilio.messages');
    $postData = [
        'From' => '+243810000100',
        'To' => '+243810000000',
        'Body' => 'Message certifie',
        'MessageSid' => 'SM999999999',
    ];

    $validator = new RequestValidator($token);
    $signature = $validator->computeSignature($url, $postData);

    $response = $this->withHeaders([
        'X-Twilio-Signature' => $signature,
    ])->post($url, $postData);

    $response->assertOk()
        ->assertJson(['message' => 'Message Twilio recu.']);

    expect(NotificationWebhookEvent::query()->where('external_message_id', 'SM999999999')->exists())->toBeTrue();
});

test('twilio webhook computes signature correctly behind railway proxy headers', function () {
    $token = 'test-token-proxy';
    Config::set('services.twilio.validate_webhook_signature', true);
    Config::set('services.twilio.auth_token', $token);

    // Derriere Railway: https://wapi-production.up.railway.app/api/webhooks/twilio/messages
    $publicUrl = 'https://wapi-production.up.railway.app/api/webhooks/twilio/messages';
    $postData = [
        'From' => 'whatsapp:+243810000100',
        'To' => 'whatsapp:+243810000000',
        'Body' => 'WhatsApp via proxy',
        'MessageSid' => 'SM_PROXY_123',
    ];

    $validator = new RequestValidator($token);
    $signature = $validator->computeSignature($publicUrl, $postData);

    $response = $this->withHeaders([
        'X-Twilio-Signature' => $signature,
        'X-Forwarded-Proto' => 'https',
        'X-Forwarded-Host' => 'wapi-production.up.railway.app',
    ])->post(route('webhooks.twilio.messages'), $postData);

    $response->assertOk();

    $event = NotificationWebhookEvent::query()->where('external_message_id', 'SM_PROXY_123')->firstOrFail();
    expect($event->channel->value)->toBe('whatsapp');
});
