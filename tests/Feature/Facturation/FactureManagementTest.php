<?php

declare(strict_types=1);

namespace Tests\Feature\Facturation;

use App\Enums\FactureStatusEnum;
use App\Enums\FactureTypeEnum;
use App\Enums\RoleEnum;
use App\Models\AuditLog;
use App\Models\Cabinet;
use App\Models\Client;
use App\Models\Dossier;
use App\Models\Facture;
use App\Models\User;
use App\Services\DossierService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Validation\ValidationException;
use Livewire\Volt\Volt;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('authorized cabinet user can create a facture with calculated totals', function () {
    $cabinet = Cabinet::factory()->create();
    $user = User::factory()->for($cabinet)->create();
    $user->assignRole(RoleEnum::Admin->value);
    $client = Client::factory()->for($cabinet)->create(['nom' => 'Kasongo']);
    $dossier = Dossier::factory()->for($cabinet)->create(['client_id' => $client->id]);

    $this->actingAs($user);

    Volt::test('factures.index')
        ->set('client_id', $client->id)
        ->set('dossier_id', $dossier->id)
        ->set('type_facture', FactureTypeEnum::Definitive->value)
        ->set('date_emission', '2026-07-20')
        ->set('date_echeance', '2026-08-20')
        ->set('taux_tva', '16.00')
        ->set('lignes', [
            ['description' => 'Honoraires', 'quantite' => '2', 'prix_unitaire' => '150'],
            ['description' => 'Frais administratifs', 'quantite' => '1', 'prix_unitaire' => '200'],
        ])
        ->call('save')
        ->assertHasNoErrors();

    $facture = Facture::query()->where('client_id', $client->id)->firstOrFail();

    expect($facture->cabinet_id)->toBe($cabinet->id)
        ->and($facture->dossier_id)->toBe($dossier->id)
        ->and($facture->statut)->toBe(FactureStatusEnum::Brouillon)
        ->and($facture->montant_ht)->toBe('500.00')
        ->and($facture->montant_tva)->toBe('80.00')
        ->and($facture->montant_ttc)->toBe('580.00')
        ->and($facture->lignes()->count())->toBe(2)
        ->and(AuditLog::query()->where('action', 'facture.created')->where('auditable_id', $facture->id)->exists())->toBeTrue();
});

test('facture listing is isolated by cabinet and supports search', function () {
    $cabinet = Cabinet::factory()->create();
    $user = User::factory()->for($cabinet)->create();
    $user->assignRole(RoleEnum::Comptable->value);
    $visible = Facture::factory()->for($cabinet)->create(['numero_facture' => 'FAC-VISIBLE-001']);

    Facture::factory()->create(['numero_facture' => 'FAC-INVISIBLE-001']);

    $this->actingAs($user);

    $this->get(route('factures.index'))
        ->assertOk()
        ->assertSee('FAC-VISIBLE-001')
        ->assertDontSee('FAC-INVISIBLE-001');

    Volt::test('factures.index')
        ->set('search', 'VISIBLE')
        ->assertSee($visible->numero_facture);
});

test('authorized user can emit and cancel an unpaid facture', function () {
    $cabinet = Cabinet::factory()->create();
    $user = User::factory()->for($cabinet)->create();
    $user->assignRole(RoleEnum::Admin->value);
    $facture = Facture::factory()->for($cabinet)->draft()->create();

    $this->actingAs($user);

    Volt::test('factures.index')
        ->call('send', $facture->id)
        ->assertHasNoErrors();

    expect($facture->refresh()->statut)->toBe(FactureStatusEnum::Envoyee);

    Volt::test('factures.index')
        ->call('cancel', $facture->id)
        ->assertHasNoErrors();

    expect($facture->refresh()->statut)->toBe(FactureStatusEnum::Annulee)
        ->and(AuditLog::query()->where('action', 'facture.cancelled')->where('auditable_id', $facture->id)->exists())->toBeTrue();
});

test('sold facture cannot be updated or cancelled', function () {
    $cabinet = Cabinet::factory()->create();
    $user = User::factory()->for($cabinet)->create();
    $user->assignRole(RoleEnum::Admin->value);
    $facture = Facture::factory()->for($cabinet)->paid()->create();
    $client = Client::factory()->for($cabinet)->create();

    $this->actingAs($user);

    Volt::test('factures.index')
        ->call('cancel', $facture->id)
        ->assertForbidden();

    Volt::test('factures.index')
        ->set('editing_id', $facture->id)
        ->set('client_id', $client->id)
        ->set('type_facture', FactureTypeEnum::Definitive->value)
        ->set('date_emission', '2026-07-20')
        ->set('date_echeance', '2026-08-20')
        ->set('taux_tva', '0')
        ->set('lignes', [['description' => 'Nouvelle ligne', 'quantite' => '1', 'prix_unitaire' => '100']])
        ->call('save')
        ->assertForbidden();
});

test('dossier cannot be closed while linked facture is not paid', function () {
    $cabinet = Cabinet::factory()->create();
    $user = User::factory()->for($cabinet)->create();
    $user->assignRole(RoleEnum::Avocat->value);
    $client = Client::factory()->for($cabinet)->create();
    $dossier = Dossier::factory()->for($cabinet)->create(['client_id' => $client->id]);
    Facture::factory()->for($cabinet)->create([
        'client_id' => $client->id,
        'dossier_id' => $dossier->id,
        'statut' => FactureStatusEnum::Envoyee,
    ]);

    expect(fn () => app(DossierService::class)->close($dossier, $user))
        ->toThrow(ValidationException::class);
});

test('dossier can be closed when linked factures are paid or cancelled', function () {
    $cabinet = Cabinet::factory()->create();
    $user = User::factory()->for($cabinet)->create();
    $user->assignRole(RoleEnum::Avocat->value);
    $client = Client::factory()->for($cabinet)->create();
    $dossier = Dossier::factory()->for($cabinet)->create(['client_id' => $client->id]);
    Facture::factory()->for($cabinet)->paid()->create([
        'client_id' => $client->id,
        'dossier_id' => $dossier->id,
    ]);
    Facture::factory()->for($cabinet)->cancelled()->create([
        'client_id' => $client->id,
        'dossier_id' => $dossier->id,
    ]);

    $closed = app(DossierService::class)->close($dossier, $user);

    expect($closed->isClosed())->toBeTrue();
});

test('facture pdf route is authorized for same cabinet user', function () {
    $cabinet = Cabinet::factory()->create();
    $user = User::factory()->for($cabinet)->create();
    $user->assignRole(RoleEnum::Comptable->value);
    $facture = Facture::factory()->for($cabinet)->create();

    $this->actingAs($user)
        ->get(route('factures.pdf', $facture))
        ->assertOk();
});
