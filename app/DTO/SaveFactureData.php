<?php

declare(strict_types=1);

namespace App\DTO;

use App\Enums\FactureTypeEnum;
use Carbon\CarbonInterface;

final readonly class SaveFactureData
{
    /**
     * @param  array<int, array{description: string, quantite: numeric-string, prix_unitaire: numeric-string}>  $lignes
     */
    public function __construct(
        public string $clientId,
        public ?string $dossierId,
        public FactureTypeEnum $typeFacture,
        public CarbonInterface $dateEmission,
        public CarbonInterface $dateEcheance,
        public string $tauxTva,
        public ?string $notesClient,
        public array $lignes,
    ) {}
}
