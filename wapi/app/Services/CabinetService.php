<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\CreateCabinetData;
use App\Enums\CabinetStatus;
use App\Models\Cabinet;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CabinetService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {}

    public function create(CreateCabinetData $data, User $actor): Cabinet
    {
        return DB::transaction(function () use ($data, $actor): Cabinet {
            $cabinet = Cabinet::query()->create($data->toModelAttributes());

            $this->auditLogService->record(
                action: 'cabinet.created',
                user: $actor,
                cabinet: $cabinet,
                auditable: $cabinet,
            );

            return $cabinet;
        });
    }

    public function suspend(Cabinet $cabinet, User $actor): Cabinet
    {
        return $this->setStatus($cabinet, CabinetStatus::Suspended, $actor, 'cabinet.suspended');
    }

    public function activate(Cabinet $cabinet, User $actor): Cabinet
    {
        return $this->setStatus($cabinet, CabinetStatus::Active, $actor, 'cabinet.activated');
    }

    private function setStatus(Cabinet $cabinet, CabinetStatus $status, User $actor, string $action): Cabinet
    {
        return DB::transaction(function () use ($cabinet, $status, $actor, $action): Cabinet {
            $cabinet->forceFill(['statut_cabinet' => $status])->save();

            $this->auditLogService->record(
                action: $action,
                user: $actor,
                cabinet: $cabinet,
                auditable: $cabinet,
            );

            return $cabinet->refresh();
        });
    }
}
