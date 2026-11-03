<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\DomaineDroitEnum;
use App\Enums\DossierStatusEnum;
use App\Enums\TypeFacturationEnum;
use App\Models\Cabinet;
use App\Models\Client;
use App\Models\Dossier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Dossier>
 */
class DossierFactory extends Factory
{
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
            'numero_reference' => 'DOS-'.fake()->unique()->numerify('######'),
            'titre_dossier' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'domaine_droit' => fake()->randomElement(DomaineDroitEnum::cases()),
            'statut_dossier' => DossierStatusEnum::Ouvert,
            'date_ouverture' => fake()->dateTimeBetween('-1 year'),
            'date_cloture' => null,
            'juridiction' => fake()->optional()->company(),
            'type_facturation' => fake()->randomElement(TypeFacturationEnum::cases()),
            'created_by' => fn (array $attributes): string => User::factory()->create([
                'cabinet_id' => $attributes['cabinet_id'],
            ])->id,
            'updated_by' => null,
        ];
    }

    public function closed(): static
    {
        return $this->state(fn (): array => [
            'statut_dossier' => DossierStatusEnum::Clos,
            'date_cloture' => now()->toDateString(),
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (): array => [
            'statut_dossier' => DossierStatusEnum::Archive,
            'date_cloture' => now()->subDay()->toDateString(),
        ]);
    }
}
