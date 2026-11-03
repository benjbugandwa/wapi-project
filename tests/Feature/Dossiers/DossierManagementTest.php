<?php

declare(strict_types=1);

namespace Tests\Feature\Dossiers;

use App\Enums\DomaineDroitEnum;
use App\Enums\DossierStatusEnum;
use App\Enums\DossierUserRoleEnum;
use App\Enums\RoleEnum;
use App\Enums\TypeFacturationEnum;
use App\Models\AuditLog;
use App\Models\Cabinet;
use App\Models\Client;
use App\Models\Dossier;
use App\Models\PartieAdverse;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Volt\Volt;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('authorized cabinet user can create a dossier linked to a client', function () {
    $cabinet = Cabinet::factory()->create();
    $user = User::factory()->for($cabinet)->create();
    $user->assignRole(RoleEnum::Avocat->value);
    $client = Client::factory()->for($cabinet)->create();

    $this->actingAs($user);

    Volt::test('dossiers.create')
        ->set('client_id', $client->id)
        ->set('numero_reference', 'DOS-2026-001')
        ->set('titre_dossier', 'Contentieux commercial')
        ->set('domaine_droit', DomaineDroitEnum::Civil->value)
        ->set('date_ouverture', '2026-07-15')
        ->set('type_facturation', TypeFacturationEnum::Forfait->value)
        ->call('save')
        ->assertHasNoErrors();

    $dossier = Dossier::query()->where('numero_reference', 'DOS-2026-001')->firstOrFail();

    expect($dossier->cabinet_id)->toBe($cabinet->id)
        ->and($dossier->client_id)->toBe($client->id)
        ->and($dossier->created_by)->toBe($user->id)
        ->and($dossier->statut_dossier)->toBe(DossierStatusEnum::Ouvert)
        ->and(AuditLog::query()->where('action', 'dossier.created')->where('auditable_id', $dossier->id)->exists())->toBeTrue();
});

test('dossier listing is filtered by cabinet and supports search', function () {
    $cabinet = Cabinet::factory()->create();
    $user = User::factory()->for($cabinet)->create();
    $user->assignRole(RoleEnum::Avocat->value);

    $visibleDossier = Dossier::factory()->for($cabinet)->create([
        'numero_reference' => 'VISIBLE-001',
        'titre_dossier' => 'Visible dossier',
    ]);

    Dossier::factory()->create([
        'numero_reference' => 'INVISIBLE-001',
        'titre_dossier' => 'Invisible dossier',
    ]);

    $this->actingAs($user);

    $this->get(route('dossiers.index'))
        ->assertOk()
        ->assertSee('VISIBLE-001')
        ->assertDontSee('INVISIBLE-001');

    Volt::test('dossiers.index')
        ->set('search', 'Visible')
        ->assertSee($visibleDossier->titre_dossier);
});

test('authorized user can update progress information but cannot delete a dossier through standard interface', function () {
    $cabinet = Cabinet::factory()->create();
    $user = User::factory()->for($cabinet)->create();
    $user->assignRole(RoleEnum::Avocat->value);
    $client = Client::factory()->for($cabinet)->create();
    $dossier = Dossier::factory()->for($cabinet)->create([
        'client_id' => $client->id,
        'numero_reference' => 'DOS-UPDATE-001',
    ]);

    $this->actingAs($user);

    Volt::test('dossiers.edit', ['dossier' => $dossier])
        ->set('titre_dossier', 'Dossier modifie')
        ->set('statut_dossier', DossierStatusEnum::EnCours->value)
        ->call('save')
        ->assertHasNoErrors();

    expect($dossier->refresh()->titre_dossier)->toBe('Dossier modifie')
        ->and($dossier->statut_dossier)->toBe(DossierStatusEnum::EnCours);

    $this->delete('/dossiers/'.$dossier->id)->assertMethodNotAllowed();
});

test('cabinet user cannot access another cabinet dossier', function () {
    $user = User::factory()->create();
    $user->assignRole(RoleEnum::Avocat->value);
    $otherDossier = Dossier::factory()->create();

    $this->actingAs($user)
        ->get(route('dossiers.show', $otherDossier))
        ->assertForbidden();
});

test('comptable can view dossiers but cannot create or edit them', function () {
    $user = User::factory()->create();
    $user->assignRole(RoleEnum::Comptable->value);
    $dossier = Dossier::factory()->create([
        'cabinet_id' => $user->cabinet_id,
    ]);

    $this->actingAs($user)
        ->get(route('dossiers.index'))
        ->assertOk();

    $this->get(route('dossiers.create'))
        ->assertForbidden();

    $this->get(route('dossiers.edit', $dossier))
        ->assertForbidden();
});

test('admin can assign collaborators with exactly one avocat responsable', function () {
    $cabinet = Cabinet::factory()->create();
    $admin = User::factory()->for($cabinet)->create();
    $admin->assignRole(RoleEnum::Admin->value);
    $responsable = User::factory()->for($cabinet)->create(['nom' => 'Avocat Responsable']);
    $responsable->assignRole(RoleEnum::Avocat->value);
    $collaborateur = User::factory()->for($cabinet)->create(['nom' => 'Secretaire Collaboratrice']);
    $collaborateur->assignRole(RoleEnum::Secretaire->value);
    $dossier = Dossier::factory()->for($cabinet)->create();

    $this->actingAs($admin);

    Volt::test('dossiers.show', ['dossier' => $dossier])
        ->set('responsable_id', $responsable->id)
        ->set('collaborateur_ids', [$collaborateur->id])
        ->call('saveAssignments')
        ->assertHasNoErrors();

    expect($dossier->assignedUsers()->whereKey($responsable->id)->wherePivot('role_dossier', DossierUserRoleEnum::Responsable->value)->exists())->toBeTrue()
        ->and($dossier->assignedUsers()->whereKey($collaborateur->id)->wherePivot('role_dossier', DossierUserRoleEnum::Collaborateur->value)->exists())->toBeTrue()
        ->and(AuditLog::query()->where('action', 'dossier.assignments_updated')->where('auditable_id', $dossier->id)->exists())->toBeTrue();
});

test('authorized user can save partie adverse information', function () {
    $cabinet = Cabinet::factory()->create();
    $user = User::factory()->for($cabinet)->create();
    $user->assignRole(RoleEnum::Avocat->value);
    $dossier = Dossier::factory()->for($cabinet)->create();

    $this->actingAs($user);

    Volt::test('dossiers.show', ['dossier' => $dossier])
        ->set('type_partie', 'personne_physique')
        ->set('nom', 'Mukendi')
        ->set('prenom', 'Paul')
        ->set('email', 'paul.mukendi@example.com')
        ->set('avocat_nom', 'Maitre adverse')
        ->call('savePartieAdverse')
        ->assertHasNoErrors();

    $partieAdverse = PartieAdverse::query()->where('dossier_id', $dossier->id)->firstOrFail();

    expect($partieAdverse->cabinet_id)->toBe($cabinet->id)
        ->and($partieAdverse->nom)->toBe('Mukendi')
        ->and(AuditLog::query()->where('action', 'partie_adverse.created')->where('auditable_id', $partieAdverse->id)->exists())->toBeTrue();
});

test('avocat can close and archive a dossier through dedicated actions', function () {
    $cabinet = Cabinet::factory()->create();
    $user = User::factory()->for($cabinet)->create();
    $user->assignRole(RoleEnum::Avocat->value);
    $dossier = Dossier::factory()->for($cabinet)->create();

    $this->actingAs($user);

    Volt::test('dossiers.show', ['dossier' => $dossier])
        ->call('close')
        ->assertHasNoErrors();

    expect($dossier->refresh()->statut_dossier)->toBe(DossierStatusEnum::Clos)
        ->and($dossier->date_cloture)->not->toBeNull();

    Volt::test('dossiers.show', ['dossier' => $dossier->refresh()])
        ->call('archive')
        ->assertHasNoErrors();

    expect($dossier->refresh()->statut_dossier)->toBe(DossierStatusEnum::Archive)
        ->and(AuditLog::query()->where('action', 'dossier.closed')->where('auditable_id', $dossier->id)->exists())->toBeTrue()
        ->and(AuditLog::query()->where('action', 'dossier.archived')->where('auditable_id', $dossier->id)->exists())->toBeTrue();
});
