<?php

declare(strict_types=1);

namespace App\DTO;

use App\Enums\MethodePaiementEnum;
use Carbon\CarbonInterface;

final readonly class CreatePaiementData
{
    public function __construct(
        public string $factureId,
        public string $montantPaye,
        public CarbonInterface $datePaiement,
        public MethodePaiementEnum $methodePaiement,
        public ?string $referenceTransaction,
    ) {}
}
