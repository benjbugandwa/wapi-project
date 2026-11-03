<?php

declare(strict_types=1);

namespace App\DTO;

use App\Models\User;

final readonly class EditUserAssignmentData
{
    public function __construct(
        public User $target,
        public string $roleName,
        public ?string $cabinetId = null,
        public ?bool $isActive = null,
    ) {}
}
