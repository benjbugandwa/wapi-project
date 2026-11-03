<?php

declare(strict_types=1);

namespace App\DTO;

use App\Enums\DomaineDroitEnum;
use App\Enums\DossierStatusEnum;
use App\Enums\TypeFacturationEnum;
use Carbon\CarbonInterface;

final readonly class UpdateDossierData
{
    public function __construct(
        public string $clientId,
        public string $numeroReference,
        public string $titreDossier,
        public DomaineDroitEnum $domaineDroit,
        public DossierStatusEnum $statutDossier,
        public CarbonInterface $dateOuverture,
        public TypeFacturationEnum $typeFacturation,
        public ?string $description = null,
        public ?string $juridiction = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toModelAttributes(): array
    {
        return [
            'client_id' => $this->clientId,
            'numero_reference' => $this->numeroReference,
            'titre_dossier' => $this->titreDossier,
            'description' => $this->description,
            'domaine_droit' => $this->domaineDroit,
            'statut_dossier' => $this->statutDossier,
            'date_ouverture' => $this->dateOuverture->toDateString(),
            'juridiction' => $this->juridiction,
            'type_facturation' => $this->typeFacturation,
        ];
    }
}
