<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ClientTypeEnum;
use App\Models\Cabinet;
use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cabinet_id' => Cabinet::factory(),
            'type_client' => ClientTypeEnum::PersonnePhysique,
            'nom' => fake()->lastName(),
            'prenom' => fake()->firstName(),
            'email' => fake()->optional()->safeEmail(),
            'telephone_principal' => fake()->optional()->phoneNumber(),
            'adresse_rue' => fake()->optional()->streetAddress(),
            'ville' => fake()->optional()->city(),
            'numero_identification' => fake()->optional()->numerify('ID-######'),
            'representant_legal' => null,
            'notes_internes' => fake()->optional()->sentence(),
            'created_by' => fn (array $attributes): string => User::factory()->create([
                'cabinet_id' => $attributes['cabinet_id'],
            ])->id,
            'updated_by' => null,
        ];
    }

    public function personneMorale(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type_client' => ClientTypeEnum::PersonneMorale,
            'nom' => fake()->company(),
            'prenom' => null,
            'representant_legal' => fake()->name(),
        ]);
    }
}
