<?php

declare(strict_types=1);

namespace App\DTO;

use App\Models\User;

final readonly class UpdateUserRoleData
{
    public function __construct(
        public User $target,
        public string $roleName,
    ) {}
}
