<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ClientTypeEnum;
use App\Models\Dossier;
use App\Models\PartieAdverse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PartieAdverse>
 */
class PartieAdverseFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'dossier_id' => Dossier::factory(),
            'cabinet_id' => fn (array $attributes): string => Dossier::query()->findOrFail($attributes['dossier_id'])->cabinet_id,
            'type_partie' => ClientTypeEnum::PersonnePhysique,
            'nom' => fake()->lastName(),
            'prenom' => fake()->firstName(),
            'email' => fake()->optional()->safeEmail(),
            'telephone' => fake()->optional()->phoneNumber(),
            'adresse' => fake()->optional()->streetAddress(),
            'avocat_nom' => fake()->optional()->name(),
            'avocat_email' => fake()->optional()->safeEmail(),
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function personneMorale(): static
    {
        return $this->state(fn (): array => [
            'type_partie' => ClientTypeEnum::PersonneMorale,
            'nom' => fake()->company(),
            'prenom' => null,
        ]);
    }
}
