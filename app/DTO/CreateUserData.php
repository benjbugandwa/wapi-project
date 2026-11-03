<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class CreateUserData
{
    public function __construct(
        public string $nom,
        public string $email,
        public string $password,
        public string $roleName,
        public ?string $cabinetId = null,
        public bool $isActive = true,
    ) {}
}
