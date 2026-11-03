<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\SavePartieAdverseData;
use App\Models\Dossier;
use App\Models\PartieAdverse;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PartieAdverseService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {}

    public function save(Dossier $dossier, SavePartieAdverseData $data, User $actor): PartieAdverse
    {
        return DB::transaction(function () use ($dossier, $data, $actor): PartieAdverse {
            $partieAdverse = $dossier->partiesAdverses()->first();
            $action = $partieAdverse === null ? 'partie_adverse.created' : 'partie_adverse.updated';

            $partieAdverse ??= new PartieAdverse([
                'cabinet_id' => $dossier->cabinet_id,
                'dossier_id' => $dossier->id,
            ]);

            $partieAdverse->fill([
                ...$data->toModelAttributes(),
                'cabinet_id' => $dossier->cabinet_id,
                'dossier_id' => $dossier->id,
            ])->save();

            $this->auditLogService->record(
                action: $action,
                user: $actor,
                cabinet: $dossier->cabinet,
                auditable: $partieAdverse,
                metadata: ['dossier_id' => $dossier->id],
            );

            return $partieAdverse->refresh();
        });
    }
}
