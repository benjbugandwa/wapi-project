<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\CreateUserData;
use App\DTO\UpdateProfileData;
use App\DTO\UpdateUserRoleData;
use App\Enums\RoleEnum;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class UserAccessService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {}

    public function updateProfile(User $user, UpdateProfileData $data): User
    {
        $user->fill($data->toModelAttributes());
        $user->save();

        return $user->refresh();
    }

    public function createUser(CreateUserData $data, User $actor): User
    {
        Role::findByName($data->roleName);

        if ($data->roleName === RoleEnum::SystemAdmin->value && ! $actor->isSystemAdmin()) {
            throw ValidationException::withMessages([
                'role_name' => __('Seul un administrateur systeme peut attribuer ce role.'),
            ]);
        }

        return DB::transaction(function () use ($data, $actor): User {
            $user = User::query()->forceCreate([
                'cabinet_id' => $data->roleName === RoleEnum::SystemAdmin->value ? null : $data->cabinetId,
                'nom' => $data->nom,
                'email' => $data->email,
                'password' => Hash::make($data->password),
                'is_active' => $data->isActive,
                'email_verified_at' => now(),
            ]);

            $user->assignRole($data->roleName);

            $this->auditLogService->record(
                action: 'user.created',
                user: $actor,
                cabinet: $user->cabinet,
                auditable: $user,
                metadata: [
                    'role_name' => $data->roleName,
                    'cabinet_id' => $user->cabinet_id,
                ],
            );

            return $user;
        });
    }

    public function activate(User $target, User $actor): User
    {
        return $this->setActiveState($target, $actor, true, 'user.activated');
    }

    public function deactivate(User $target, User $actor): User
    {
        return $this->setActiveState($target, $actor, false, 'user.deactivated');
    }

    public function updateRole(UpdateUserRoleData $data, User $actor): User
    {
        Role::findByName($data->roleName);

        if ($data->roleName === RoleEnum::SystemAdmin->value && ! $actor->isSystemAdmin()) {
            throw ValidationException::withMessages([
                'roleName' => __('Seul un administrateur systeme peut attribuer ce role.'),
            ]);
        }

        return DB::transaction(function () use ($data, $actor): User {
            $data->target->syncRoles([$data->roleName]);

            $this->auditLogService->record(
                action: 'user.role_updated',
                user: $actor,
                cabinet: $data->target->cabinet,
                auditable: $data->target,
                metadata: ['role_name' => $data->roleName],
            );

            return $data->target->refresh();
        });
    }

    private function setActiveState(User $target, User $actor, bool $isActive, string $action): User
    {
        return DB::transaction(function () use ($target, $actor, $isActive, $action): User {
            $target->forceFill(['is_active' => $isActive])->save();

            $this->auditLogService->record(
                action: $action,
                user: $actor,
                cabinet: $target->cabinet,
                auditable: $target,
                metadata: ['is_active' => $isActive],
            );

            return $target->refresh();
        });
    }
}
