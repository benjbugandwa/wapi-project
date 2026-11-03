<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CabinetStatus;
use App\Models\Cabinet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cabinet>
 */
class CabinetFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'denomination' => fake()->company(),
            'adresse_cabinet' => fake()->address(),
            'description' => fake()->optional()->sentence(),
            'nom_repondant' => fake()->name(),
            'telephone_repondant' => fake()->phoneNumber(),
            'email_repondant' => fake()->optional()->safeEmail(),
            'statut_cabinet' => CabinetStatus::Active,
            'logo_cabinet' => null,
        ];
    }

    public function suspended(): static
    {
        return $this->state(fn (array $attributes): array => [
            'statut_cabinet' => CabinetStatus::Suspended,
        ]);
    }
}
