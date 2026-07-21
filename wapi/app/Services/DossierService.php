<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\CreateDossierData;
use App\DTO\UpdateDossierData;
use App\Enums\DossierStatusEnum;
use App\Models\Client;
use App\Models\Dossier;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class DossierService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {}

    public function create(CreateDossierData $data, User $actor): Dossier
    {
        $this->ensureActorHasCabinet($actor);

        return DB::transaction(function () use ($data, $actor): Dossier {
            $client = $this->clientForActor($data->clientId, $actor);

            $dossier = Dossier::query()->create([
                ...$data->toModelAttributes(),
                'cabinet_id' => $actor->cabinet_id,
                'client_id' => $client->id,
                'statut_dossier' => DossierStatusEnum::Ouvert,
                'created_by' => $actor->id,
            ]);

            $this->auditLogService->record(
                action: 'dossier.created',
                user: $actor,
                cabinet: $dossier->cabinet,
                auditable: $dossier,
            );

            return $dossier;
        });
    }

    public function update(Dossier $dossier, UpdateDossierData $data, User $actor): Dossier
    {
        if (in_array($data->statutDossier, [DossierStatusEnum::Clos, DossierStatusEnum::Archive], true)) {
            throw ValidationException::withMessages([
                'statut_dossier' => __('La cloture et l archivage doivent utiliser les actions metier dediees.'),
            ]);
        }

        return DB::transaction(function () use ($dossier, $data, $actor): Dossier {
            $client = $this->clientForDossier($data->clientId, $dossier);

            $dossier->fill([
                ...$data->toModelAttributes(),
                'client_id' => $client->id,
                'updated_by' => $actor->id,
            ])->save();

            $this->auditLogService->record(
                action: 'dossier.updated',
                user: $actor,
                cabinet: $dossier->cabinet,
                auditable: $dossier,
            );

            return $dossier->refresh();
        });
    }

    public function updateStatus(Dossier $dossier, DossierStatusEnum $status, User $actor): Dossier
    {
        return match ($status) {
            DossierStatusEnum::Clos => $this->close($dossier, $actor),
            DossierStatusEnum::Archive => $this->archive($dossier, $actor),
            default => $this->setProgressStatus($dossier, $status, $actor),
        };
    }

    public function close(Dossier $dossier, User $actor): Dossier
    {
        $this->ensureDossierCanBeClosed($dossier);

        return DB::transaction(function () use ($dossier, $actor): Dossier {
            $dossier->fill([
                'statut_dossier' => DossierStatusEnum::Clos,
                'date_cloture' => now()->toDateString(),
                'updated_by' => $actor->id,
            ])->save();

            $this->auditLogService->record(
                action: 'dossier.closed',
                user: $actor,
                cabinet: $dossier->cabinet,
                auditable: $dossier,
            );

            return $dossier->refresh();
        });
    }

    public function archive(Dossier $dossier, User $actor): Dossier
    {
        if (! $dossier->isClosed()) {
            throw ValidationException::withMessages([
                'statut_dossier' => __('Seul un dossier cloture peut etre archive.'),
            ]);
        }

        return DB::transaction(function () use ($dossier, $actor): Dossier {
            $dossier->fill([
                'statut_dossier' => DossierStatusEnum::Archive,
                'updated_by' => $actor->id,
            ])->save();

            $this->auditLogService->record(
                action: 'dossier.archived',
                user: $actor,
                cabinet: $dossier->cabinet,
                auditable: $dossier,
            );

            return $dossier->refresh();
        });
    }

    private function setProgressStatus(Dossier $dossier, DossierStatusEnum $status, User $actor): Dossier
    {
        return DB::transaction(function () use ($dossier, $status, $actor): Dossier {
            $dossier->fill([
                'statut_dossier' => $status,
                'date_cloture' => null,
                'updated_by' => $actor->id,
            ])->save();

            $this->auditLogService->record(
                action: 'dossier.status_updated',
                user: $actor,
                cabinet: $dossier->cabinet,
                auditable: $dossier,
                metadata: ['statut_dossier' => $status->value],
            );

            return $dossier->refresh();
        });
    }

    private function ensureDossierCanBeClosed(Dossier $dossier): void
    {
        if (! Schema::hasTable('factures')) {
            return;
        }

        $blockingInvoices = DB::table('factures')
            ->where('dossier_id', $dossier->id)
            ->whereNotIn('statut', ['soldee', 'annulee'])
            ->exists();

        if (! $blockingInvoices) {
            return;
        }

        throw ValidationException::withMessages([
            'statut_dossier' => __('Le dossier ne peut etre cloture que lorsque toutes ses factures non annulees sont soldees.'),
        ]);
    }

    private function clientForActor(string $clientId, User $actor): Client
    {
        return Client::query()
            ->whereKey($clientId)
            ->where('cabinet_id', $actor->cabinet_id)
            ->firstOrFail();
    }

    private function clientForDossier(string $clientId, Dossier $dossier): Client
    {
        return Client::query()
            ->whereKey($clientId)
            ->where('cabinet_id', $dossier->cabinet_id)
            ->firstOrFail();
    }

    private function ensureActorHasCabinet(User $actor): void
    {
        if ($actor->cabinet_id !== null) {
            return;
        }

        throw ValidationException::withMessages([
            'cabinet' => __('Un dossier doit etre cree depuis un cabinet actif.'),
        ]);
    }
}
