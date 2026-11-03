<?php

declare(strict_types=1);

namespace Tests\Feature\Agenda;

use App\Enums\NotificationChannelEnum;
use App\Enums\RoleEnum;
use App\Jobs\SendRappelNotificationJob;
use App\Models\Cabinet;
use App\Models\Evenement;
use App\Models\NotificationPreference;
use App\Models\NotificationWebhookEvent;
use App\Models\Rappel;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Queue;
use Livewire\Volt\Volt;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    Queue::fake();
});

test('authorized user can configure notification preferences', function () {
    $user = User::factory()->create();
    $user->assignRole(RoleEnum::Avocat->value);

    $this->actingAs($user);

    Volt::test('agenda.index')
        ->set('preference_enabled.email', true)
        ->set('preference_enabled.sms', true)
        ->set('preference_destinations.email', $user->email)
        ->set('preference_destinations.sms', '+243000000002')
        ->call('savePreferences')
        ->assertHasNoErrors();

    expect(NotificationPreference::query()->where('user_id', $user->id)->where('canal_notification', NotificationChannelEnum::Sms->value)->where('is_enabled', true)->exists())->toBeTrue();
});

test('authorized user can configure reminders for event participants', function () {
    $cabinet = Cabinet::factory()->create();
    $user = User::factory()->for($cabinet)->create();
    $user->assignRole(RoleEnum::Avocat->value);
    $evenement = Evenement::factory()->create([
        'cabinet_id' => $cabinet->id,
        'created_by' => $user->id,
        'date_debut' => now()->addDay(),
        'date_fin' => now()->addDay()->addHour(),
    ]);
    $evenement->participants()->attach($user->id, [
        'id' => (string) str()->uuid(),
        'cabinet_id' => $cabinet->id,
    ]);

    $this->actingAs($user);

    Volt::test('agenda.show', ['evenement' => $evenement])
        ->set('delai_minutes', 30)
        ->set('channels', [NotificationChannelEnum::Email->value])
        ->call('saveRappels')
        ->assertHasNoErrors();

    $rappel = Rappel::query()->where('evenement_id', $evenement->id)->firstOrFail();

    expect($rappel->delai_minutes)->toBe(30)
        ->and($rappel->date_declenchement->equalTo($evenement->date_debut->copy()->subMinutes(30)))->toBeTrue();

    Queue::assertPushed(SendRappelNotificationJob::class);
});

test('twilio webhook stores incoming sms or whatsapp message', function () {
    $this->postJson('/api/webhooks/twilio/messages', [
        'MessageSid' => 'SM123',
        'From' => 'whatsapp:+243000000003',
        'To' => 'whatsapp:+243000000004',
        'Body' => 'Confirmation recue',
    ])->assertOk();

    $event = NotificationWebhookEvent::query()->where('external_message_id', 'SM123')->firstOrFail();

    expect($event->channel)->toBe(NotificationChannelEnum::WhatsApp)
        ->and($event->body)->toBe('Confirmation recue')
        ->and($event->processed_at)->not->toBeNull();
});
