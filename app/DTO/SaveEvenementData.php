<?php

declare(strict_types=1);

namespace App\DTO;

use App\Enums\EvenementTypeEnum;
use Carbon\CarbonInterface;

final readonly class SaveEvenementData
{
    /**
     * @param  array<int, string>  $participantIds
     */
    public function __construct(
        public string $titre,
        public EvenementTypeEnum $typeEvenement,
        public CarbonInterface $dateDebut,
        public CarbonInterface $dateFin,
        public ?string $dossierId = null,
        public ?string $lieu = null,
        public ?string $description = null,
        public array $participantIds = [],
    ) {}
}
