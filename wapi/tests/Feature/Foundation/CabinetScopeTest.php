<?php

declare(strict_types=1);

namespace Tests\Feature\Foundation;

use App\Enums\ClientTypeEnum;
use App\Enums\RoleEnum;
use App\Models\Cabinet;
use App\Models\Client;
use App\Models\Dossier;
use App\Models\Facture;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('cabinet scope automatically isolates models by authenticated user cabinet', function () {
    $cabinetA = Cabinet::factory()->create(['denomination' => 'Cabinet Alpha']);
    $cabinetB = Cabinet::factory()->create(['denomination' => 'Cabinet Beta']);

    $userA = User::factory()->for($cabinetA)->create();
    $userA->assignRole(RoleEnum::Avocat->value);

    // Entites appartenant au Cabinet A
    $clientA = Client::factory()->for($cabinetA)->create(['nom' => 'Client Cabinet A']);
    $dossierA = Dossier::factory()->for($cabinetA)->create(['titre_dossier' => 'Affaire Cabinet A']);
    $factureA = Facture::factory()->for($cabinetA)->create();

    // Entites appartenant au Cabinet B
    $clientB = Client::factory()->for($cabinetB)->create(['nom' => 'Client Cabinet B']);
    $dossierB = Dossier::factory()->for($cabinetB)->create(['titre_dossier' => 'Affaire Cabinet B']);
    $factureB = Facture::factory()->for($cabinetB)->create();

    $this->actingAs($userA);

    // Execution de requetes Eloquent directes sans where manual
    $clients = Client::all();
    $dossiers = Dossier::all();
    $factures = Facture::all();

    expect($clients->pluck('id'))->toContain($clientA->id)
        ->and($clients->pluck('id'))->not->toContain($clientB->id)
        ->and($dossiers->pluck('id'))->toContain($dossierA->id)
        ->and($dossiers->pluck('id'))->not->toContain($dossierB->id)
        ->and($factures->pluck('id'))->toContain($factureA->id)
        ->and($factures->pluck('id'))->not->toContain($factureB->id);
});

test('cabinet id is automatically assigned on creating when user is authenticated', function () {
    $cabinet = Cabinet::factory()->create();
    $user = User::factory()->for($cabinet)->create();
    $user->assignRole(RoleEnum::Avocat->value);

    $this->actingAs($user);

    // Creation d'un client sans specifier cabinet_id
    $client = new Client([
        'nom' => 'Auto Tenant',
        'type_client' => ClientTypeEnum::PersonnePhysique,
        'email' => 'autotenant@example.com',
        'telephone_principal' => '+243 810 000 999',
        'created_by' => $user->id,
    ]);
    $client->save();

    expect($client->cabinet_id)->toBe($cabinet->id);
});

test('system admin bypasses cabinet scope automatically', function () {
    $cabinetA = Cabinet::factory()->create();
    $cabinetB = Cabinet::factory()->create();

    $systemAdmin = User::factory()->systemAdmin()->create();
    $systemAdmin->assignRole(RoleEnum::SystemAdmin->value);

    $clientA = Client::factory()->for($cabinetA)->create();
    $clientB = Client::factory()->for($cabinetB)->create();

    $this->actingAs($systemAdmin);

    $allClients = Client::all();

    expect($allClients->pluck('id'))->toContain($clientA->id, $clientB->id);
});
