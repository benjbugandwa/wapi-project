<?php

declare(strict_types=1);

namespace App\DTO;

use App\Enums\CabinetStatus;

final readonly class CreateCabinetData
{
    public function __construct(
        public string $denomination,
        public string $adresseCabinet,
        public string $nomRepondant,
        public string $telephoneRepondant,
        public ?string $description = null,
        public ?string $emailRepondant = null,
        public ?string $logoCabinet = null,
        public CabinetStatus $statutCabinet = CabinetStatus::Active,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toModelAttributes(): array
    {
        return [
            'denomination' => $this->denomination,
            'adresse_cabinet' => $this->adresseCabinet,
            'description' => $this->description,
            'nom_repondant' => $this->nomRepondant,
            'telephone_repondant' => $this->telephoneRepondant,
            'email_repondant' => $this->emailRepondant,
            'statut_cabinet' => $this->statutCabinet,
            'logo_cabinet' => $this->logoCabinet,
        ];
    }
}
