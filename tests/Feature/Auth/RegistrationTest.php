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
        ->assertSee('Numéro de téléphone')
        ->assertSee('+243')
        ->assertSee('Creer mon compte');
});

test('new users can register directly without invitation', function () {
    $component = Volt::test('pages.auth.register')
        ->set('nom', 'Nouvel Avocat')
        ->set('email', 'avocat.nouveau@wapi.test')
        ->set('telephone', '970480293')
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123');

    $component->call('register');

    $component
        ->assertHasNoErrors()
        ->assertRedirect('/');

    expect(session('status'))->toBe(__('Vous serez notifié une fois que votre compte sera activé par un administrateur.'));

    $user = User::query()->where('email', 'avocat.nouveau@wapi.test')->first();

    expect($user)->not->toBeNull()
        ->and($user->nom)->toBe('Nouvel Avocat')
        ->and($user->phone_number)->toBe('+243970480293')
        ->and($user->is_active)->toBeFalse()
        ->and($user->cabinet_id)->toBeNull();

    $this->assertGuest();
});

test('registration normalizes phone number entered with leading zero', function () {
    Volt::test('pages.auth.register')
        ->set('nom', 'Avocat Avec Zero')
        ->set('email', 'avocat.zero@wapi.test')
        ->set('telephone', '0970480293')
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123')
        ->call('register')
        ->assertHasNoErrors();

    $user = User::query()->where('email', 'avocat.zero@wapi.test')->first();

    expect($user)->not->toBeNull()
        ->and($user->phone_number)->toBe('+243970480293');
});

test('registration normalizes phone number entered with international format and spaces', function () {
    Volt::test('pages.auth.register')
        ->set('nom', 'Avocat International')
        ->set('email', 'avocat.inter@wapi.test')
        ->set('telephone', '+243 97 048 02 93')
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123')
        ->call('register')
        ->assertHasNoErrors();

    $user = User::query()->where('email', 'avocat.inter@wapi.test')->first();

    expect($user)->not->toBeNull()
        ->and($user->phone_number)->toBe('+243970480293');
});

test('registration validates required fields, email format, phone and password confirmation', function () {
    Volt::test('pages.auth.register')
        ->set('nom', '')
        ->set('email', 'invalid-email')
        ->set('telephone', '')
        ->set('password', 'short')
        ->set('password_confirmation', 'mismatch')
        ->call('register')
        ->assertHasErrors(['nom', 'email', 'telephone', 'password']);
});

test('registration rejects invalid phone number formats', function () {
    Volt::test('pages.auth.register')
        ->set('nom', 'Avocat Invalide')
        ->set('email', 'invalide@wapi.test')
        ->set('telephone', '12345') // trop court
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123')
        ->call('register')
        ->assertHasErrors(['telephone']);
});

test('registration rejects duplicate phone number', function () {
    User::factory()->create([
        'phone_number' => '+243970480293',
    ]);

    Volt::test('pages.auth.register')
        ->set('nom', 'Deuxieme Avocat')
        ->set('email', 'autre.avocat@wapi.test')
        ->set('telephone', '970480293')
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123')
        ->call('register')
        ->assertHasErrors(['telephone']);
});

test('welcome screen displays activation waiting status message', function () {
    $this->withSession([
        'status' => 'Vous serez notifié une fois que votre compte sera activé par un administrateur.',
    ])
        ->get('/')
        ->assertOk()
        ->assertSee('Vous serez notifié une fois que votre compte sera activé par un administrateur.');
});


