<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\NotificationChannelEnum;
use App\Models\Evenement;
use App\Models\Rappel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Rappel>
 */
class RappelFactory extends Factory
{
    protected $model = Rappel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $evenement = Evenement::factory()->create();

        return [
            'cabinet_id' => $evenement->cabinet_id,
            'evenement_id' => $evenement->id,
            'user_id' => User::factory()->create(['cabinet_id' => $evenement->cabinet_id])->id,
            'canal_notification' => NotificationChannelEnum::Email,
            'delai_minutes' => 60,
            'date_declenchement' => $evenement->date_debut->copy()->subMinutes(60),
            'est_envoye' => false,
            'sent_at' => null,
        ];
    }
}
