<?php

declare(strict_types=1);

namespace Tests\Feature\Clients;

use App\Enums\ClientTypeEnum;
use App\Enums\RoleEnum;
use App\Models\AuditLog;
use App\Models\Cabinet;
use App\Models\Client;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Volt\Volt;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('authorized cabinet user can create a client', function () {
    $user = User::factory()->create();
    $user->assignRole(RoleEnum::Avocat->value);

    $this->actingAs($user);

    Volt::test('clients.create')
        ->set('type_client', ClientTypeEnum::PersonnePhysique->value)
        ->set('nom', 'Mbuyi')
        ->set('prenom', 'Jean')
        ->set('email', 'jean.mbuyi@example.com')
        ->set('telephone_principal', '+243 810 000 100')
        ->call('save')
        ->assertHasNoErrors();

    $client = Client::query()->where('email', 'jean.mbuyi@example.com')->firstOrFail();

    expect($client->cabinet_id)->toBe($user->cabinet_id)
        ->and($client->created_by)->toBe($user->id)
        ->and(AuditLog::query()->where('action', 'client.created')->where('auditable_id', $client->id)->exists())->toBeTrue();
});

test('client listing is filtered by cabinet and supports search', function () {
    $cabinet = Cabinet::factory()->create();
    $user = User::factory()->for($cabinet)->create();
    $user->assignRole(RoleEnum::Avocat->value);

    $visibleClient = Client::factory()->for($cabinet)->create(['nom' => 'Visible']);
    Client::factory()->create(['nom' => 'Invisible']);

    $this->actingAs($user);

    $this->get(route('clients.index'))
        ->assertOk()
        ->assertSee('Visible')
        ->assertDontSee('Invisible');

    Volt::test('clients.index')
        ->set('search', 'Visible')
        ->assertSee($visibleClient->nom);
});

test('authorized cabinet user can update and soft delete own client', function () {
    $cabinet = Cabinet::factory()->create();
    $user = User::factory()->for($cabinet)->create();
    $user->assignRole(RoleEnum::Admin->value);
    $client = Client::factory()->for($cabinet)->create();

    $this->actingAs($user);

    Volt::test('clients.edit', ['client' => $client])
        ->set('nom', 'Nom Modifie')
        ->set('type_client', ClientTypeEnum::PersonneMorale->value)
        ->call('save')
        ->assertHasNoErrors();

    expect($client->refresh()->nom)->toBe('Nom Modifie')
        ->and($client->type_client)->toBe(ClientTypeEnum::PersonneMorale);

    Volt::test('clients.index')
        ->call('delete', $client->id)
        ->assertHasNoErrors();

    $this->assertSoftDeleted($client);
    expect(AuditLog::query()->where('auditable_id', $client->id)->where('action', 'client.deleted')->exists())->toBeTrue();
});

test('cabinet user cannot access another cabinet client', function () {
    $user = User::factory()->create();
    $user->assignRole(RoleEnum::Avocat->value);
    $otherClient = Client::factory()->create();

    $this->actingAs($user)
        ->get(route('clients.show', $otherClient))
        ->assertForbidden();
});

test('comptable can view clients but cannot create them', function () {
    $user = User::factory()->create();
    $user->assignRole(RoleEnum::Comptable->value);

    $this->actingAs($user)
        ->get(route('clients.index'))
        ->assertOk();

    $this->get(route('clients.create'))
        ->assertForbidden();
});
