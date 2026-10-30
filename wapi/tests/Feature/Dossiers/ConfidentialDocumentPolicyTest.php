<?php

declare(strict_types=1);

namespace Tests\Feature\Dossiers;

use App\Enums\DossierUserRoleEnum;
use App\Enums\RoleEnum;
use App\Models\Cabinet;
use App\Models\Document;
use App\Models\Dossier;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    Storage::fake('local');
});

test('cabinet admin can download confidential document', function () {
    $cabinet = Cabinet::factory()->create();
    $admin = User::factory()->for($cabinet)->create();
    $admin->assignRole(RoleEnum::Admin->value);

    $dossier = Dossier::factory()->for($cabinet)->create();
    $document = Document::factory()->forDossier()->create([
        'cabinet_id' => $cabinet->id,
        'dossier_id' => $dossier->id,
        'est_confidentiel' => true,
        'chemin_acces' => 'dossiers/confidential.pdf',
    ]);
    Storage::disk('local')->put($document->chemin_acces, 'secret data');

    $this->actingAs($admin)
        ->get(route('documents.download', $document))
        ->assertOk();
});

test('assigned avocat can download confidential document', function () {
    $cabinet = Cabinet::factory()->create();
    $assignedAvocat = User::factory()->for($cabinet)->create();
    $assignedAvocat->assignRole(RoleEnum::Avocat->value);

    $dossier = Dossier::factory()->for($cabinet)->create();
    // Affectation de l'avocat au dossier
    $dossier->assignedUsers()->attach($assignedAvocat->id, [
        'id' => (string) Str::uuid(),
        'cabinet_id' => $cabinet->id,
        'role_dossier' => DossierUserRoleEnum::Responsable->value,
    ]);

    $document = Document::factory()->forDossier()->create([
        'cabinet_id' => $cabinet->id,
        'dossier_id' => $dossier->id,
        'est_confidentiel' => true,
        'chemin_acces' => 'dossiers/confidential-assigned.pdf',
    ]);
    Storage::disk('local')->put($document->chemin_acces, 'secret data');

    $this->actingAs($assignedAvocat)
        ->get(route('documents.download', $document))
        ->assertOk();
});

test('unassigned avocat cannot download confidential document', function () {
    $cabinet = Cabinet::factory()->create();
    $unassignedAvocat = User::factory()->for($cabinet)->create();
    $unassignedAvocat->assignRole(RoleEnum::Avocat->value);

    $dossier = Dossier::factory()->for($cabinet)->create();
    // L'avocat n'est pas assigne au dossier

    $document = Document::factory()->forDossier()->create([
        'cabinet_id' => $cabinet->id,
        'dossier_id' => $dossier->id,
        'est_confidentiel' => true,
        'chemin_acces' => 'dossiers/confidential-unassigned.pdf',
    ]);
    Storage::disk('local')->put($document->chemin_acces, 'secret data');

    $this->actingAs($unassignedAvocat)
        ->get(route('documents.download', $document))
        ->assertForbidden();
});

test('comptable and stagiaire cannot download confidential document', function () {
    $cabinet = Cabinet::factory()->create();
    $comptable = User::factory()->for($cabinet)->create();
    $comptable->assignRole(RoleEnum::Comptable->value);

    $stagiaire = User::factory()->for($cabinet)->create();
    $stagiaire->assignRole(RoleEnum::Stagiaire->value);

    $dossier = Dossier::factory()->for($cabinet)->create();
    $document = Document::factory()->forDossier()->create([
        'cabinet_id' => $cabinet->id,
        'dossier_id' => $dossier->id,
        'est_confidentiel' => true,
        'chemin_acces' => 'dossiers/confidential-restricted.pdf',
    ]);
    Storage::disk('local')->put($document->chemin_acces, 'secret data');

    $this->actingAs($comptable)
        ->get(route('documents.download', $document))
        ->assertForbidden();

    $this->actingAs($stagiaire)
        ->get(route('documents.download', $document))
        ->assertForbidden();
});

test('non confidential document remains accessible according to standard permissions', function () {
    $cabinet = Cabinet::factory()->create();
    $unassignedAvocat = User::factory()->for($cabinet)->create();
    $unassignedAvocat->assignRole(RoleEnum::Avocat->value);

    $dossier = Dossier::factory()->for($cabinet)->create();
    $document = Document::factory()->forDossier()->create([
        'cabinet_id' => $cabinet->id,
        'dossier_id' => $dossier->id,
        'est_confidentiel' => false,
        'chemin_acces' => 'dossiers/public.pdf',
    ]);
    Storage::disk('local')->put($document->chemin_acces, 'standard data');

    // Un avocat non assigne du meme cabinet peut telecharger un document ordinaire non confidentiel
    $this->actingAs($unassignedAvocat)
        ->get(route('documents.download', $document))
        ->assertOk();
});
