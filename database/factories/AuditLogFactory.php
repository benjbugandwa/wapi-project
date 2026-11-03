<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\Cabinet;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cabinet_id' => Cabinet::factory(),
            'user_id' => fn (array $attributes): string => User::factory()->create([
                'cabinet_id' => $attributes['cabinet_id'],
            ])->id,
            'action' => fake()->randomElement(['created', 'updated', 'deleted']),
            'auditable_type' => null,
            'auditable_id' => null,
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'metadata' => ['source' => 'factory'],
        ];
    }
}
