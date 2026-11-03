<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Livewire\Volt\Volt;

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response
        ->assertOk()
        ->assertSeeVolt('pages.auth.register')
        ->assertSee('Attribution du role et du cabinet')
        ->assertSee('Creer mon compte');
});

test('new users can register directly without invitation', function () {
    $component = Volt::test('pages.auth.register')
        ->set('nom', 'Nouvel Avocat')
        ->set('email', 'avocat.nouveau@wapi.test')
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123');

    $component->call('register');

    $component
        ->assertHasNoErrors()
        ->assertRedirect(route('login', absolute: false));

    $user = User::query()->where('email', 'avocat.nouveau@wapi.test')->first();

    expect($user)->not->toBeNull()
        ->and($user->nom)->toBe('Nouvel Avocat')
        ->and($user->is_active)->toBeFalse()
        ->and($user->cabinet_id)->toBeNull();

    $this->assertGuest();
});

test('registration validates required fields, email format and password confirmation', function () {
    Volt::test('pages.auth.register')
        ->set('nom', '')
        ->set('email', 'invalid-email')
        ->set('password', 'short')
        ->set('password_confirmation', 'mismatch')
        ->call('register')
        ->assertHasErrors(['nom', 'email', 'password']);
});
