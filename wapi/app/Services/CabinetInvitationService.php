<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\AcceptCabinetInvitationData;
use App\DTO\CreateCabinetInvitationData;
use App\Enums\RoleEnum;
use App\Models\CabinetInvitation;
use App\Models\User;
use App\Notifications\CabinetInvitationNotification;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class CabinetInvitationService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {}

    /**
     * @return array{invitation: CabinetInvitation, token: string}
     */
    public function create(CreateCabinetInvitationData $data): array
    {
        $this->ensureAssignableRole($data->roleName, $data->inviter);

        return DB::transaction(function () use ($data): array {
            $rawToken = Str::random(64);

            $invitation = CabinetInvitation::query()->updateOrCreate(
                [
                    'cabinet_id' => $data->cabinet->id,
                    'email' => $data->email,
                ],
                [
                    'role_name' => $data->roleName,
                    'token' => $this->hashToken($rawToken),
                    'invited_by' => $data->inviter->id,
                    'accepted_at' => null,
                    'expires_at' => $data->expiresAt,
                ],
            );

            $invitation->notify(new CabinetInvitationNotification($rawToken, $data->cabinet));

            $this->auditLogService->record(
                action: 'cabinet_invitation.created',
                user: $data->inviter,
                cabinet: $data->cabinet,
                auditable: $invitation,
                metadata: ['email' => $data->email, 'role_name' => $data->roleName],
            );

            return ['invitation' => $invitation, 'token' => $rawToken];
        });
    }

    public function accept(AcceptCabinetInvitationData $data): User
    {
        $invitation = $this->findPendingByToken($data->token);

        if ($invitation === null) {
            throw ValidationException::withMessages([
                'token' => __('Cette invitation est invalide ou expiree.'),
            ]);
        }

        if (User::query()->where('email', $invitation->email)->exists()) {
            throw ValidationException::withMessages([
                'email' => __('Un compte existe deja avec cette adresse email.'),
            ]);
        }

        return DB::transaction(function () use ($data, $invitation): User {
            $user = User::query()->create([
                'cabinet_id' => $invitation->cabinet_id,
                'nom' => $data->nom,
                'email' => $invitation->email,
                'password' => Hash::make($data->password),
                'is_active' => false,
            ]);

            $user->assignRole($invitation->role_name);

            $invitation->forceFill(['accepted_at' => now()])->save();

            event(new Registered($user));

            $this->auditLogService->record(
                action: 'cabinet_invitation.accepted',
                user: $user,
                cabinet: $invitation->cabinet,
                auditable: $invitation,
                metadata: ['role_name' => $invitation->role_name],
            );

            return $user;
        });
    }

    public function findPendingByToken(string $token): ?CabinetInvitation
    {
        return CabinetInvitation::query()
            ->with(['cabinet', 'inviter'])
            ->where('token', $this->hashToken($token))
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->first();
    }

    private function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    private function ensureAssignableRole(string $roleName, User $actor): void
    {
        Role::findByName($roleName);

        if ($roleName === RoleEnum::SystemAdmin->value && ! $actor->isSystemAdmin()) {
            throw ValidationException::withMessages([
                'roleName' => __('Seul un administrateur systeme peut attribuer ce role.'),
            ]);
        }
    }
}
