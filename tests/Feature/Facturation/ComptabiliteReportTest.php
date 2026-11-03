<?php

declare(strict_types=1);

namespace Tests\Feature\Facturation;

use App\Enums\FactureStatusEnum;
use App\Enums\RoleEnum;
use App\Models\Cabinet;
use App\Models\Client;
use App\Models\Facture;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Volt\Volt;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('authorized user can view overdue invoices and client situation for own cabinet', function () {
    $cabinet = Cabinet::factory()->create();
    $user = User::factory()->for($cabinet)->create();
    $user->assignRole(RoleEnum::Comptable->value);
    $client = Client::factory()->for($cabinet)->create(['nom' => 'Client Rapport']);
    $visible = Facture::factory()->for($cabinet)->create([
        'client_id' => $client->id,
        'numero_facture' => 'FAC-RETARD-001',
        'date_echeance' => now()->subDays(5)->toDateString(),
        'statut' => FactureStatusEnum::Envoyee,
    ]);

    Facture::factory()->create([
        'numero_facture' => 'FAC-AUTRE-CABINET',
        'date_echeance' => now()->subDays(5)->toDateString(),
        'statut' => FactureStatusEnum::Envoyee,
    ]);

    $this->actingAs($user);

    $this->get(route('comptabilite.rapports'))
        ->assertOk()
        ->assertSee('FAC-RETARD-001')
        ->assertDontSee('FAC-AUTRE-CABINET');

    Volt::test('comptabilite.rapports')
        ->set('client_id', $client->id)
        ->assertSee($visible->numero_facture);
});
