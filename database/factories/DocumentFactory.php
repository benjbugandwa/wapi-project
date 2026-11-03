<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Client;
use App\Models\Document;
use App\Models\Dossier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'cabinet_id' => fn (array $attributes): string => Client::query()->findOrFail($attributes['client_id'])->cabinet_id,
            'dossier_id' => null,
            'nom_original' => 'piece-identite.pdf',
            'nom_systeme' => Str::uuid().'.pdf',
            'chemin_acces' => 'client-documents/'.Str::uuid().'.pdf',
            'extension' => 'pdf',
            'type_mime' => 'application/pdf',
            'taille_octets' => 1024,
            'categorie' => 'piece_identite',
            'est_confidentiel' => true,
            'uploaded_by' => fn (array $attributes): string => User::factory()->create([
                'cabinet_id' => $attributes['cabinet_id'],
            ])->id,
        ];
    }

    public function forDossier(): static
    {
        return $this->state(function (): array {
            $dossier = Dossier::factory()->create();

            return [
                'client_id' => null,
                'dossier_id' => $dossier->id,
                'cabinet_id' => $dossier->cabinet_id,
                'chemin_acces' => 'dossier-documents/'.fake()->uuid().'.pdf',
            ];
        });
    }
}
