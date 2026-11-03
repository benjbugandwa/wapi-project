<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class AcceptCabinetInvitationData
{
    public function __construct(
        public string $token,
        public string $nom,
        public string $password,
    ) {}
}
