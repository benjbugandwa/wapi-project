<?php

declare(strict_types=1);

namespace App\DTO;

use App\Models\Cabinet;
use App\Models\User;
use Carbon\CarbonImmutable;

final readonly class CreateCabinetInvitationData
{
    public function __construct(
        public Cabinet $cabinet,
        public string $email,
        public string $roleName,
        public User $inviter,
        public CarbonImmutable $expiresAt,
    ) {}
}
