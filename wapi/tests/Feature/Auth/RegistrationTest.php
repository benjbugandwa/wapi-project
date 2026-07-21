<?php

namespace Tests\Feature\Auth;

use App\DTO\CreateCabinetInvitationData;
use App\Enums\RoleEnum;
use App\Models\Cabinet;
use App\Models\User;
use App\Notifications\CabinetInvitationNotification;
use App\Services\CabinetInvitationService;
use Carbon\CarbonImmutable;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Notification;
use Livewire\Volt\Volt;

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response
        ->assertOk()
        ->assertSeeVolt('pages.auth.register')
        ->assertSee('L inscription necessite une invitation cabinet valide');
});

test('new users cannot register without invitation', function () {
    $component = Volt::test('pages.auth.register')
        ->set('nom', 'Test User')
        ->set('password', 'password')
        ->set('password_confirmation', 'password');

    $component->call('register');

    $component
        ->assertHasErrors('token')
        ->assertNoRedirect();

    $this->assertGuest();
});

test('new users can accept a cabinet invitation', function () {
    Notification::fake();
    $this->seed(RolePermissionSeeder::class);

    $cabinet = Cabinet::factory()->create();
    $inviter = User::factory()->for($cabinet)->create();
    $inviter->assignRole(RoleEnum::Admin->value);

    $payload = app(CabinetInvitationService::class)->create(new CreateCabinetInvitationData(
        cabinet: $cabinet,
        email: 'invitee@example.com',
        roleName: RoleEnum::Avocat->value,
        inviter: $inviter,
        expiresAt: CarbonImmutable::now()->addDays(7),
    ));

    Notification::assertSentTimes(CabinetInvitationNotification::class, 1);

    $component = Volt::test('pages.auth.register', ['token' => $payload['token']])
        ->set('nom', 'Invitee User')
        ->set('password', 'password')
        ->set('password_confirmation', 'password');

    $component->call('register');

    $component
        ->assertHasNoErrors()
        ->assertRedirect(route('login', absolute: false));

    $user = User::query()->where('email', 'invitee@example.com')->firstOrFail();

    expect($user->cabinet->is($cabinet))->toBeTrue()
        ->and($user->is_active)->toBeFalse()
        ->and($user->hasRole(RoleEnum::Avocat->value))->toBeTrue();

    $this->assertGuest();
});
