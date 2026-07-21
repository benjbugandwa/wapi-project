<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\DossierUserRoleEnum;
use App\Enums\RoleEnum;
use App\Models\Dossier;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DossierAssignmentService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {}

    /**
     * @param  array<int, array{user_id: string, role_dossier: string}>  $assignments
     */
    public function sync(Dossier $dossier, array $assignments, User $actor): void
    {
        $normalizedAssignments = $this->normalizedAssignments($dossier, $assignments);

        DB::transaction(function () use ($dossier, $normalizedAssignments, $actor): void {
            $dossier->assignedUsers()->sync($normalizedAssignments);

            $this->auditLogService->record(
                action: 'dossier.assignments_updated',
                user: $actor,
                cabinet: $dossier->cabinet,
                auditable: $dossier,
                metadata: [
                    'assigned_user_ids' => array_keys($normalizedAssignments),
                ],
            );
        });
    }

    /**
     * @param  array<int, array{user_id: string, role_dossier: string}>  $assignments
     * @return array<string, array{id: string, cabinet_id: string, role_dossier: string}>
     */
    private function normalizedAssignments(Dossier $dossier, array $assignments): array
    {
        $normalized = [];
        $responsableCount = 0;

        foreach ($assignments as $assignment) {
            $userId = $assignment['user_id'] ?? null;
            $role = $assignment['role_dossier'] ?? null;

            if (! is_string($userId) || ! is_string($role) || $userId === '') {
                continue;
            }

            $roleEnum = DossierUserRoleEnum::from($role);
            $user = User::query()
                ->whereKey($userId)
                ->where('cabinet_id', $dossier->cabinet_id)
                ->firstOrFail();

            if ($roleEnum === DossierUserRoleEnum::Responsable) {
                $responsableCount++;

                if (! $user->hasRole(RoleEnum::Avocat->value)) {
                    throw ValidationException::withMessages([
                        'assignments' => __('Le responsable du dossier doit avoir le role avocat.'),
                    ]);
                }
            }

            $normalized[$user->id] = [
                'id' => (string) Str::uuid(),
                'cabinet_id' => $dossier->cabinet_id,
                'role_dossier' => $roleEnum->value,
            ];
        }

        if ($responsableCount !== 1) {
            throw ValidationException::withMessages([
                'assignments' => __('Un dossier doit avoir exactement un avocat responsable.'),
            ]);
        }

        return $normalized;
    }
}
