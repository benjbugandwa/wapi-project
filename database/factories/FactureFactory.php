<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\FactureStatusEnum;
use App\Enums\FactureTypeEnum;
use App\Models\Cabinet;
use App\Models\Client;
use App\Models\Dossier;
use App\Models\Facture;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Facture>
 */
class FactureFactory extends Factory
{
    protected $model = Facture::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cabinet_id' => Cabinet::factory(),
            'client_id' => fn (array $attributes): string => Client::factory()->create([
                'cabinet_id' => $attributes['cabinet_id'],
            ])->id,
            'dossier_id' => fn (array $attributes): string => Dossier::factory()->create([
                'cabinet_id' => $attributes['cabinet_id'],
                'client_id' => $attributes['client_id'],
            ])->id,
            'numero_facture' => 'FAC-'.now()->format('Y').'-'.fake()->unique()->numerify('####'),
            'type_facture' => FactureTypeEnum::Definitive,
            'statut' => FactureStatusEnum::Envoyee,
            'date_emission' => now()->toDateString(),
            'date_echeance' => now()->addDays(30)->toDateString(),
            'montant_ht' => '1000.00',
            'taux_tva' => '16.00',
            'montant_tva' => '160.00',
            'montant_ttc' => '1160.00',
            'notes_client' => null,
            'created_by' => fn (array $attributes): string => User::factory()->create([
                'cabinet_id' => $attributes['cabinet_id'],
            ])->id,
            'updated_by' => null,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (): array => [
            'statut' => FactureStatusEnum::Brouillon,
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn (): array => [
            'statut' => FactureStatusEnum::Soldee,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (): array => [
            'statut' => FactureStatusEnum::Annulee,
        ]);
    }
}
