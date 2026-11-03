<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Facture;
use App\Models\LigneFacture;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LigneFacture>
 */
class LigneFactureFactory extends Factory
{
    protected $model = LigneFacture::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $facture = Facture::factory()->create();

        return [
            'cabinet_id' => $facture->cabinet_id,
            'facture_id' => $facture->id,
            'description' => fake()->sentence(),
            'quantite' => '1.00',
            'prix_unitaire' => '1000.00',
            'total_ligne_ht' => '1000.00',
        ];
    }
}
