<?php

declare(strict_types=1);

namespace Tests\Feature\Facturation;

use App\Enums\FactureStatusEnum;
use App\Enums\MethodePaiementEnum;
use App\Enums\RoleEnum;
use App\Models\AuditLog;
use App\Models\Cabinet;
use App\Models\Facture;
use App\Models\Paiement;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Volt\Volt;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('authorized user can register a partial payment from facture detail', function () {
    $cabinet = Cabinet::factory()->create();
    $user = User::factory()->for($cabinet)->create();
    $user->assignRole(RoleEnum::Comptable->value);
    $facture = Facture::factory()->for($cabinet)->create([
        'montant_ht' => '1000.00',
        'montant_tva' => '0.00',
        'montant_ttc' => '1000.00',
        'statut' => FactureStatusEnum::Envoyee,
    ]);

    $this->actingAs($user);

    Volt::test('factures.show', ['facture' => $facture])
        ->set('montant_paye', '400')
        ->set('date_paiement', '2026-07-20')
        ->set('methode_paiement', MethodePaiementEnum::Virement->value)
        ->set('reference_transaction', 'TRX-400')
        ->call('savePayment')
        ->assertHasNoErrors();

    $paiement = Paiement::query()->where('facture_id', $facture->id)->firstOrFail();

    expect($paiement->cabinet_id)->toBe($cabinet->id)
        ->and($paiement->montant_paye)->toBe('400.00')
        ->and($facture->refresh()->statut)->toBe(FactureStatusEnum::PayeePartiellement)
        ->and($facture->remainingAmount())->toBe('600.00')
        ->and(AuditLog::query()->where('action', 'paiement.created')->where('auditable_id', $paiement->id)->exists())->toBeTrue();
});

test('full payment marks facture as paid', function () {
    $cabinet = Cabinet::factory()->create();
    $user = User::factory()->for($cabinet)->create();
    $user->assignRole(RoleEnum::Comptable->value);
    $facture = Facture::factory()->for($cabinet)->create([
        'montant_ht' => '1000.00',
        'montant_tva' => '0.00',
        'montant_ttc' => '1000.00',
        'statut' => FactureStatusEnum::Envoyee,
    ]);

    $this->actingAs($user);

    Volt::test('factures.show', ['facture' => $facture])
        ->set('montant_paye', '1000')
        ->set('date_paiement', '2026-07-20')
        ->set('methode_paiement', MethodePaiementEnum::Cheque->value)
        ->call('savePayment')
        ->assertHasNoErrors();

    expect($facture->refresh()->statut)->toBe(FactureStatusEnum::Soldee)
        ->and($facture->remainingAmount())->toBe('0.00');
});

test('payment cannot exceed facture remaining amount', function () {
    $cabinet = Cabinet::factory()->create();
    $user = User::factory()->for($cabinet)->create();
    $user->assignRole(RoleEnum::Comptable->value);
    $facture = Facture::factory()->for($cabinet)->create([
        'montant_ht' => '500.00',
        'montant_tva' => '0.00',
        'montant_ttc' => '500.00',
        'statut' => FactureStatusEnum::Envoyee,
    ]);

    $this->actingAs($user);

    Volt::test('factures.show', ['facture' => $facture])
        ->set('montant_paye', '600')
        ->set('date_paiement', '2026-07-20')
        ->set('methode_paiement', MethodePaiementEnum::Virement->value)
        ->call('savePayment')
        ->assertHasErrors('montant_paye');

    expect(Paiement::query()->where('facture_id', $facture->id)->exists())->toBeFalse();
});

test('payment listing is isolated by cabinet and supports search', function () {
    $cabinet = Cabinet::factory()->create();
    $user = User::factory()->for($cabinet)->create();
    $user->assignRole(RoleEnum::Comptable->value);
    $facture = Facture::factory()->for($cabinet)->create(['numero_facture' => 'FAC-PAY-001']);
    $visible = Paiement::factory()->create([
        'cabinet_id' => $cabinet->id,
        'facture_id' => $facture->id,
        'reference_transaction' => 'PAY-VISIBLE',
        'created_by' => $user->id,
    ]);

    Paiement::factory()->create(['reference_transaction' => 'PAY-INVISIBLE']);

    $this->actingAs($user);

    $this->get(route('paiements.index'))
        ->assertOk()
        ->assertSee('PAY-VISIBLE')
        ->assertDontSee('PAY-INVISIBLE');

    Volt::test('paiements.index')
        ->set('search', 'VISIBLE')
        ->assertSee($visible->reference_transaction);
});

test('payment receipt route is generated from paiement and authorized', function () {
    $cabinet = Cabinet::factory()->create();
    $user = User::factory()->for($cabinet)->create();
    $user->assignRole(RoleEnum::Comptable->value);
    $facture = Facture::factory()->for($cabinet)->create();
    $paiement = Paiement::factory()->create([
        'cabinet_id' => $cabinet->id,
        'facture_id' => $facture->id,
        'created_by' => $user->id,
    ]);

    $this->actingAs($user)
        ->get(route('paiements.receipt', $paiement))
        ->assertOk();
});
