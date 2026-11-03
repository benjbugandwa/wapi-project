<?php

declare(strict_types=1);

namespace Tests\Feature\Notification;

use App\Enums\NotificationChannelEnum;
use App\Models\User;
use App\Notifications\AccountActivatedNotification;
use App\Notifications\Channels\TwilioSmsChannel;
use App\Notifications\Channels\TwilioWhatsAppChannel;
use App\Services\TwilioNotificationGateway;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

test('resend mail configuration is properly defined and defaults to resend in app config', function () {
    $mailConfig = require config_path('mail.php');

    expect($mailConfig['default'])->toBe(env('MAIL_MAILER', 'resend'))
        ->and(config('mail.mailers.resend.transport'))->toBe('resend');
});

test('twilio gateway sends sms formatted correctly', function () {
    Http::fake([
        'https://api.twilio.com/*' => Http::response(['sid' => 'SM123456'], 200),
    ]);

    Config::set('services.twilio.account_sid', 'AC_TEST_SID');
    Config::set('services.twilio.auth_token', 'AUTH_TOKEN_TEST');
    Config::set('services.twilio.sms_from', '+10000000000');

    $gateway = new TwilioNotificationGateway;
    $success = $gateway->send(NotificationChannelEnum::Sms, '+243 97 048 0293', 'Test message');

    expect($success)->toBeTrue();

    Http::assertSent(function ($request) {
        return $request->url() === 'https://api.twilio.com/2010-04-01/Accounts/AC_TEST_SID/Messages.json'
            && $request['To'] === '+243970480293'
            && $request['From'] === '+10000000000'
            && $request['Body'] === 'Test message';
    });
});

test('twilio gateway automatically prepends whatsapp prefix to from and recipient', function () {
    Http::fake([
        'https://api.twilio.com/*' => Http::response(['sid' => 'WA123456'], 200),
    ]);

    Config::set('services.twilio.account_sid', 'AC_TEST_SID');
    Config::set('services.twilio.auth_token', 'AUTH_TOKEN_TEST');
    Config::set('services.twilio.whatsapp_from', '+14155238886'); // sans préfixe whatsapp:

    $gateway = new TwilioNotificationGateway;
    $success = $gateway->send(NotificationChannelEnum::WhatsApp, '+243970480293', 'Hello WhatsApp');

    expect($success)->toBeTrue();

    Http::assertSent(function ($request) {
        return $request['To'] === 'whatsapp:+243970480293'
            && $request['From'] === 'whatsapp:+14155238886'
            && $request['Body'] === 'Hello WhatsApp';
    });
});

test('twilio gateway safely handles missing credentials', function () {
    Config::set('services.twilio.account_sid', null);
    Config::set('services.twilio.auth_token', null);

    $gateway = new TwilioNotificationGateway;
    $success = $gateway->send(NotificationChannelEnum::Sms, '+243970480293', 'Test');

    expect($success)->toBeFalse();
});

test('twilio sms channel delivers through notification system', function () {
    Http::fake([
        'https://api.twilio.com/*' => Http::response(['sid' => 'SM999'], 200),
    ]);

    Config::set('services.twilio.account_sid', 'AC_TEST_SID');
    Config::set('services.twilio.auth_token', 'AUTH_TOKEN_TEST');
    Config::set('services.twilio.sms_from', '+10000000000');

    $user = User::factory()->make([
        'phone_number' => '+243970480293',
        'nom' => 'Maître Test',
    ]);

    $notification = new AccountActivatedNotification;
    $channel = app(TwilioSmsChannel::class);

    $sent = $channel->send($user, $notification);

    expect($sent)->toBeTrue();

    Http::assertSent(function ($request) {
        return $request['To'] === '+243970480293'
            && str_contains($request['Body'], 'Votre compte a été activé');
    });
});

test('twilio whatsapp channel delivers through notification system', function () {
    Http::fake([
        'https://api.twilio.com/*' => Http::response(['sid' => 'WA999'], 200),
    ]);

    Config::set('services.twilio.account_sid', 'AC_TEST_SID');
    Config::set('services.twilio.auth_token', 'AUTH_TOKEN_TEST');
    Config::set('services.twilio.whatsapp_from', 'whatsapp:+14155238886');

    $user = User::factory()->make([
        'phone_number' => '+243970480293',
        'nom' => 'Maître Test',
    ]);

    $notification = new AccountActivatedNotification;
    $channel = app(TwilioWhatsAppChannel::class);

    $sent = $channel->send($user, $notification);

    expect($sent)->toBeTrue();

    Http::assertSent(function ($request) {
        return $request['To'] === 'whatsapp:+243970480293'
            && str_contains($request['Body'], 'Wapi');
    });
});

test('artisan test-notification command validates parameters and sends test sms', function () {
    Http::fake([
        'https://api.twilio.com/*' => Http::response(['sid' => 'SMTEST'], 200),
    ]);

    Config::set('services.twilio.account_sid', 'AC_TEST_SID');
    Config::set('services.twilio.auth_token', 'AUTH_TOKEN_TEST');
    Config::set('services.twilio.sms_from', '+10000000000');

    $this->artisan('wapi:test-notification', [
        'channel' => 'sms',
        'recipient' => '+243970480293',
    ])
        ->expectsOutputToContain('transmise à Twilio avec succès')
        ->assertSuccessful();
});

test('artisan test-notification command handles email testing', function () {
    Mail::fake();

    $this->artisan('wapi:test-notification', [
        'channel' => 'email',
        'recipient' => 'test@wapi.cd',
    ])
        ->expectsOutputToContain('Email envoyé avec succès')
        ->assertSuccessful();

    Mail::assertSentCount(1);
});
