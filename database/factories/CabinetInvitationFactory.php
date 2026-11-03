<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\RoleEnum;
use App\Models\Cabinet;
use App\Models\CabinetInvitation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CabinetInvitation>
 */
class CabinetInvitationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cabinet_id' => Cabinet::factory(),
            'email' => fake()->unique()->safeEmail(),
            'role_name' => RoleEnum::Avocat->value,
            'token' => hash('sha256', Str::random(64)),
            'invited_by' => fn (array $attributes): string => User::factory()->create([
                'cabinet_id' => $attributes['cabinet_id'],
            ])->id,
            'accepted_at' => null,
            'expires_at' => now()->addDays(7),
        ];
    }

    public function accepted(): static
    {
        return $this->state(fn (array $attributes): array => [
            'accepted_at' => now(),
        ]);
    }
}
