<?php

declare(strict_types=1);

namespace Tests\Feature\Dashboard;

use App\Enums\CabinetStatus;
use App\Enums\DossierStatusEnum;
use App\Enums\DossierUserRoleEnum;
use App\Enums\EvenementStatusEnum;
use App\Enums\EvenementTypeEnum;
use App\Enums\FactureStatusEnum;
use App\Enums\RoleEnum;
use App\Models\Cabinet;
use App\Models\Client;
use App\Models\Document;
use App\Models\Dossier;
use App\Models\Evenement;
use App\Models\Facture;
use App\Models\Paiement;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Str;
use Livewire\Volt\Volt;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('avocat dashboard displays assigned active dossiers, upcoming hearings and urgent deadlines', function () {
    $cabinet = Cabinet::factory()->create();
    $avocat = User::factory()->for($cabinet)->create(['nom' => 'Me Alain']);
    $avocat->assignRole(RoleEnum::Avocat->value);

    $client = Client::factory()->for($cabinet)->create(['created_by' => $avocat->id]);

    // Active assigned dossier
    $dossier = Dossier::factory()->for($cabinet)->create([
        'client_id' => $client->id,
        'created_by' => $avocat->id,
        'numero_reference' => 'DOS-AVOCAT-001',
        'statut_dossier' => DossierStatusEnum::EnCours,
    ]);
    $avocat->assignedDossiers()->attach($dossier->id, [
        'id' => (string) Str::uuid(),
        'cabinet_id' => $cabinet->id,
        'role_dossier' => DossierUserRoleEnum::Responsable->value,
    ]);

    // Upcoming audience in 5 days
    $audience = Evenement::factory()->for($cabinet)->create([
        'dossier_id' => $dossier->id,
        'created_by' => $avocat->id,
        'titre' => 'Audience de plaidoirie Tribunal',
        'type_evenement' => EvenementTypeEnum::Audience,
        'statut_evenement' => EvenementStatusEnum::Planifie,
        'date_debut' => now()->addDays(5)->setHour(10)->setMinute(0),
        'date_fin' => now()->addDays(5)->setHour(12)->setMinute(0),
    ]);

    // Urgent deadline in 3 days
    $tacheUrgente = Evenement::factory()->for($cabinet)->create([
        'dossier_id' => $dossier->id,
        'created_by' => $avocat->id,
        'titre' => 'Dépôt des conclusions écrites',
        'type_evenement' => EvenementTypeEnum::DateButoir,
        'statut_evenement' => EvenementStatusEnum::Planifie,
        'date_debut' => now()->addDays(3)->setHour(18)->setMinute(0),
    ]);

    $this->actingAs($avocat);

    Volt::test('dashboard')
        ->assertSee('DOS-AVOCAT-001')
        ->assertSee('Audience de plaidoirie Tribunal')
        ->assertSee('Dépôt des conclusions écrites')
        ->assertSee('Dossiers actifs en charge')
        ->assertSee('Audiences à 15 jours');

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('DOS-AVOCAT-001')
        ->assertSee('Audience de plaidoirie Tribunal');
});

test('comptable dashboard displays monthly billing, payments, overdue alerts and quick action buttons', function () {
    $cabinet = Cabinet::factory()->create();
    $comptable = User::factory()->for($cabinet)->create(['nom' => 'Mme Sophie']);
    $comptable->assignRole(RoleEnum::Comptable->value);

    $client = Client::factory()->for($cabinet)->create(['created_by' => $comptable->id]);

    // Facture of the month
    $factureMois = Facture::factory()->for($cabinet)->create([
        'client_id' => $client->id,
        'created_by' => $comptable->id,
        'numero_facture' => 'FACT-MOIS-2026',
        'date_emission' => now()->startOfMonth()->addDays(2),
        'date_echeance' => now()->addDays(15),
        'montant_ht' => '1000.00',
        'montant_tva' => '160.00',
        'montant_ttc' => '1160.00',
        'statut' => FactureStatusEnum::Envoyee,
    ]);

    // Partial payment of the month
    Paiement::factory()->for($cabinet)->create([
        'facture_id' => $factureMois->id,
        'created_by' => $comptable->id,
        'montant_paye' => '500.00',
        'date_paiement' => now()->startOfMonth()->addDays(5),
    ]);

    // Overdue invoice > 30 days
    $factureRetard = Facture::factory()->for($cabinet)->create([
        'client_id' => $client->id,
        'created_by' => $comptable->id,
        'numero_facture' => 'FACT-RETARD-45J',
        'date_emission' => now()->subDays(60),
        'date_echeance' => now()->subDays(45),
        'montant_ht' => '2000.00',
        'montant_tva' => '320.00',
        'montant_ttc' => '2320.00',
        'statut' => FactureStatusEnum::Envoyee,
    ]);

    $this->actingAs($comptable);

    Volt::test('dashboard')
        ->assertSee('FACT-RETARD-45J')
        ->assertSee('Facturé ce mois')
        ->assertSee('Encaissé ce mois')
        ->assertSee('Créer une facture')
        ->assertSee('Enregistrer un paiement')
        ->assertSee('Rapports & Soldes clients');

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('FACT-RETARD-45J');
});

test('admin cabinet dashboard displays collaborators, occupation rate and unassigned dossier alerts', function () {
    $cabinet = Cabinet::factory()->create(['denomination' => 'Cabinet Alpha Juridique']);
    $admin = User::factory()->for($cabinet)->create(['nom' => 'Me Directeur']);
    $admin->assignRole(RoleEnum::Admin->value);

    $collaborateur = User::factory()->for($cabinet)->create(['nom' => 'Me Junior']);
    $collaborateur->assignRole(RoleEnum::Avocat->value);

    $client = Client::factory()->for($cabinet)->create(['created_by' => $admin->id]);

    // Active dossier WITHOUT responsable avocat
    $dossierSansResponsable = Dossier::factory()->for($cabinet)->create([
        'client_id' => $client->id,
        'created_by' => $admin->id,
        'numero_reference' => 'DOS-SANS-RESP-999',
        'statut_dossier' => DossierStatusEnum::EnCours,
    ]);

    $this->actingAs($admin);

    Volt::test('dashboard')
        ->assertSee('DOS-SANS-RESP-999')
        ->assertSee('Collaborateurs actifs')
        ->assertSee('Taux d occupation')
        ->assertSee('Action requise : Dossiers sans avocat responsable désigné')
        ->assertSee('Me Junior');

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('DOS-SANS-RESP-999')
        ->assertSee('Me Junior');
});

test('system admin dashboard displays cabinets list, subscription status and storage quotas', function () {
    $systemAdmin = User::factory()->create(['nom' => 'Super Admin']);
    $systemAdmin->assignRole(RoleEnum::SystemAdmin->value);

    $cabinet1 = Cabinet::factory()->create([
        'denomination' => 'Cabinet Kinshasa Law',
        'statut_cabinet' => CabinetStatus::Active,
    ]);

    $cabinet2 = Cabinet::factory()->create([
        'denomination' => 'Cabinet Lubumbashi Conseil',
        'statut_cabinet' => CabinetStatus::Suspended,
    ]);

    // Storage document for cabinet 1 (50 Mo)
    Document::factory()->for($cabinet1)->create([
        'nom_original' => 'contrat_important.pdf',
        'taille_octets' => 52428800, // ~50 Mo
        'uploaded_by' => $systemAdmin->id,
    ]);

    $this->actingAs($systemAdmin);

    Volt::test('dashboard')
        ->assertSee('Cabinet Kinshasa Law')
        ->assertSee('Cabinet Lubumbashi Conseil')
        ->assertSee('Cabinets inscrits')
        ->assertSee('Abonnements actifs')
        ->assertSee('Quotas et consommation de stockage des documents')
        ->call('suspendCabinet', $cabinet1->id);

    expect($cabinet1->fresh()->statut_cabinet)->toBe(CabinetStatus::Suspended);

    Volt::test('dashboard')
        ->call('activateCabinet', $cabinet1->id);

    expect($cabinet1->fresh()->statut_cabinet)->toBe(CabinetStatus::Active);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Cabinet Kinshasa Law');
});

test('authorized multi-role user can switch dashboard view dynamically', function () {
    $cabinet = Cabinet::factory()->create();
    $user = User::factory()->for($cabinet)->create(['nom' => 'Me Polyvalent']);
    $user->assignRole(RoleEnum::Admin->value);
    $user->assignRole(RoleEnum::Avocat->value);

    $this->actingAs($user);

    Volt::test('dashboard')
        ->assertSet('currentRole', RoleEnum::Admin->value)
        ->assertSee('Collaborateurs actifs')
        ->call('switchRole', RoleEnum::Avocat->value)
        ->assertSet('currentRole', RoleEnum::Avocat->value)
        ->assertSee('Dossiers actifs en charge');
});
