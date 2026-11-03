<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\MethodePaiementEnum;
use App\Models\Facture;
use App\Models\Paiement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Paiement>
 */
class PaiementFactory extends Factory
{
    protected $model = Paiement::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $facture = Facture::factory()->create();

        return [
            'cabinet_id' => $facture->cabinet_id,
            'facture_id' => $facture->id,
            'montant_paye' => '100.00',
            'date_paiement' => now()->toDateString(),
            'methode_paiement' => MethodePaiementEnum::Virement,
            'reference_transaction' => fake()->optional()->bothify('PAY-####'),
            'created_by' => User::factory()->create(['cabinet_id' => $facture->cabinet_id])->id,
        ];
    }
}
