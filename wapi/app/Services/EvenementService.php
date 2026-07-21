<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\SaveEvenementData;
use App\Enums\EvenementStatusEnum;
use App\Jobs\CancelExternalCalendarEventJob;
use App\Jobs\SyncEvenementToExternalCalendarJob;
use App\Models\Dossier;
use App\Models\Evenement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EvenementService
{
    public function __construct(
        private readonly AgendaSharingService $sharingService,
        private readonly AgendaNotificationService $notificationService,
        private readonly AuditLogService $auditLogService,
    ) {}

    public function create(SaveEvenementData $data, User $actor): Evenement
    {
        $this->ensureActorHasCabinet($actor);
        $this->ensureDatesAreValid($data);

        return DB::transaction(function () use ($data, $actor): Evenement {
            $dossier = $this->dossierForActor($data->dossierId, $actor);

            $evenement = Evenement::query()->create([
                'cabinet_id' => $actor->cabinet_id,
                'dossier_id' => $dossier?->id,
                'titre' => $data->titre,
                'type_evenement' => $data->typeEvenement,
                'statut_evenement' => EvenementStatusEnum::Planifie,
                'date_debut' => $data->dateDebut,
                'date_fin' => $data->dateFin,
                'lieu' => $data->lieu,
                'description' => $data->description,
                'created_by' => $actor->id,
            ]);

            $this->sharingService->sync($evenement, $data->participantIds);

            $this->auditLogService->record(
                action: 'agenda.created',
                user: $actor,
                cabinet: $evenement->cabinet,
                auditable: $evenement,
            );

            $this->notificationService->notifyParticipants($evenement->refresh(), 'creation');
            SyncEvenementToExternalCalendarJob::dispatch($evenement);

            return $evenement->refresh();
        });
    }

    public function update(Evenement $evenement, SaveEvenementData $data, User $actor): Evenement
    {
        $this->ensureDatesAreValid($data);

        return DB::transaction(function () use ($evenement, $data, $actor): Evenement {
            $dossier = $this->dossierForEvenement($data->dossierId, $evenement);

            $evenement->fill([
                'dossier_id' => $dossier?->id,
                'titre' => $data->titre,
                'type_evenement' => $data->typeEvenement,
                'statut_evenement' => EvenementStatusEnum::Modifie,
                'date_debut' => $data->dateDebut,
                'date_fin' => $data->dateFin,
                'lieu' => $data->lieu,
                'description' => $data->description,
                'updated_by' => $actor->id,
            ])->save();

            $this->sharingService->sync($evenement, $data->participantIds);

            $this->auditLogService->record(
                action: 'agenda.updated',
                user: $actor,
                cabinet: $evenement->cabinet,
                auditable: $evenement,
            );

            $this->notificationService->notifyParticipants($evenement->refresh(), 'modification');
            SyncEvenementToExternalCalendarJob::dispatch($evenement);

            return $evenement->refresh();
        });
    }

    public function cancel(Evenement $evenement, User $actor): Evenement
    {
        return DB::transaction(function () use ($evenement, $actor): Evenement {
            $evenement->forceFill([
                'statut_evenement' => EvenementStatusEnum::Annule,
                'cancelled_at' => now(),
                'cancelled_by' => $actor->id,
                'updated_by' => $actor->id,
            ])->save();

            $this->auditLogService->record(
                action: 'agenda.cancelled',
                user: $actor,
                cabinet: $evenement->cabinet,
                auditable: $evenement,
            );

            $this->notificationService->notifyParticipants($evenement->refresh(), 'annulation');
            CancelExternalCalendarEventJob::dispatch($evenement);
            $evenement->delete();

            return $evenement;
        });
    }

    private function ensureActorHasCabinet(User $actor): void
    {
        if ($actor->cabinet_id !== null) {
            return;
        }

        throw ValidationException::withMessages([
            'cabinet' => __('Un evenement doit etre cree depuis un cabinet actif.'),
        ]);
    }

    private function ensureDatesAreValid(SaveEvenementData $data): void
    {
        if ($data->dateFin->greaterThan($data->dateDebut)) {
            return;
        }

        throw ValidationException::withMessages([
            'date_fin' => __('La date de fin doit etre posterieure a la date de debut.'),
        ]);
    }

    private function dossierForActor(?string $dossierId, User $actor): ?Dossier
    {
        if ($dossierId === null || $dossierId === '') {
            return null;
        }

        return Dossier::query()
            ->whereKey($dossierId)
            ->where('cabinet_id', $actor->cabinet_id)
            ->firstOrFail();
    }

    private function dossierForEvenement(?string $dossierId, Evenement $evenement): ?Dossier
    {
        if ($dossierId === null || $dossierId === '') {
            return null;
        }

        return Dossier::query()
            ->whereKey($dossierId)
            ->where('cabinet_id', $evenement->cabinet_id)
            ->firstOrFail();
    }
}
