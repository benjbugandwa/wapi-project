<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\EvenementStatusEnum;
use App\Enums\EvenementTypeEnum;
use App\Models\Cabinet;
use App\Models\Dossier;
use App\Models\Evenement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Evenement>
 */
class EvenementFactory extends Factory
{
    protected $model = Evenement::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $cabinet = Cabinet::factory();

        return [
            'cabinet_id' => $cabinet,
            'dossier_id' => null,
            'titre' => fake()->sentence(4),
            'type_evenement' => fake()->randomElement(EvenementTypeEnum::cases()),
            'statut_evenement' => EvenementStatusEnum::Planifie,
            'date_debut' => now()->addDays(3)->setTime(9, 0),
            'date_fin' => now()->addDays(3)->setTime(10, 0),
            'lieu' => fake()->city(),
            'description' => fake()->optional()->paragraph(),
            'created_by' => User::factory()->for($cabinet),
            'updated_by' => null,
            'cancelled_at' => null,
            'cancelled_by' => null,
        ];
    }

    public function forDossier(?Dossier $dossier = null): static
    {
        return $this->state(function (array $attributes) use ($dossier): array {
            $dossier ??= Dossier::factory()->create();

            return [
                'cabinet_id' => $dossier->cabinet_id,
                'dossier_id' => $dossier->id,
                'created_by' => User::factory()->create(['cabinet_id' => $dossier->cabinet_id])->id,
            ];
        });
    }
}
