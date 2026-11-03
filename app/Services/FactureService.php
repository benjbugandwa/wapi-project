<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\SaveFactureData;
use App\Enums\FactureStatusEnum;
use App\Models\Client;
use App\Models\Dossier;
use App\Models\Facture;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FactureService
{
    public function __construct(
        private readonly FactureCalculationService $calculationService,
        private readonly FactureNumberService $numberService,
        private readonly AuditLogService $auditLogService,
    ) {}

    public function create(SaveFactureData $data, User $actor): Facture
    {
        $this->ensureActorHasCabinet($actor);

        return DB::transaction(function () use ($data, $actor): Facture {
            [$client, $dossier] = $this->resolveClientAndDossier($data, $actor->cabinet_id);
            $calculation = $this->calculationService->calculate($data->lignes, $data->tauxTva);

            $facture = Facture::query()->create([
                'cabinet_id' => $actor->cabinet_id,
                'client_id' => $client->id,
                'dossier_id' => $dossier?->id,
                'numero_facture' => $this->numberService->nextForCabinet($actor->cabinet_id),
                'type_facture' => $data->typeFacture,
                'statut' => FactureStatusEnum::Brouillon,
                'date_emission' => $data->dateEmission,
                'date_echeance' => $data->dateEcheance,
                'montant_ht' => $calculation['montant_ht'],
                'taux_tva' => $data->tauxTva,
                'montant_tva' => $calculation['montant_tva'],
                'montant_ttc' => $calculation['montant_ttc'],
                'notes_client' => $data->notesClient,
                'created_by' => $actor->id,
            ]);

            $this->syncLines($facture, $calculation['lignes']);
            $this->record('facture.created', $facture, $actor);

            return $facture->refresh();
        });
    }

    public function update(Facture $facture, SaveFactureData $data, User $actor): Facture
    {
        $this->ensureEditable($facture);

        return DB::transaction(function () use ($facture, $data, $actor): Facture {
            [$client, $dossier] = $this->resolveClientAndDossier($data, $facture->cabinet_id);
            $calculation = $this->calculationService->calculate($data->lignes, $data->tauxTva);

            $facture->fill([
                'client_id' => $client->id,
                'dossier_id' => $dossier?->id,
                'type_facture' => $data->typeFacture,
                'date_emission' => $data->dateEmission,
                'date_echeance' => $data->dateEcheance,
                'montant_ht' => $calculation['montant_ht'],
                'taux_tva' => $data->tauxTva,
                'montant_tva' => $calculation['montant_tva'],
                'montant_ttc' => $calculation['montant_ttc'],
                'notes_client' => $data->notesClient,
                'updated_by' => $actor->id,
            ])->save();

            $this->syncLines($facture, $calculation['lignes']);
            $this->record('facture.updated', $facture, $actor);

            return $facture->refresh();
        });
    }

    public function send(Facture $facture, User $actor): Facture
    {
        $this->ensureEditable($facture);

        $facture->forceFill([
            'statut' => FactureStatusEnum::Envoyee,
            'updated_by' => $actor->id,
        ])->save();

        $this->record('facture.sent', $facture, $actor);

        return $facture->refresh();
    }

    public function cancel(Facture $facture, User $actor): Facture
    {
        if ($facture->isPaid()) {
            throw ValidationException::withMessages([
                'statut' => __('Une facture soldee ne peut pas etre annulee. Creez un avoir ou une nouvelle facture.'),
            ]);
        }

        $facture->forceFill([
            'statut' => FactureStatusEnum::Annulee,
            'updated_by' => $actor->id,
        ])->save();

        $this->record('facture.cancelled', $facture, $actor);

        return $facture->refresh();
    }

    private function ensureEditable(Facture $facture): void
    {
        if (! $facture->isPaid() && ! $facture->isCancelled()) {
            return;
        }

        throw ValidationException::withMessages([
            'statut' => __('Une facture soldee ou annulee ne peut plus etre modifiee.'),
        ]);
    }

    /**
     * @return array{0: Client, 1: Dossier|null}
     */
    private function resolveClientAndDossier(SaveFactureData $data, string $cabinetId): array
    {
        $client = Client::query()
            ->whereKey($data->clientId)
            ->where('cabinet_id', $cabinetId)
            ->firstOrFail();

        if ($data->dossierId === null || $data->dossierId === '') {
            return [$client, null];
        }

        $dossier = Dossier::query()
            ->whereKey($data->dossierId)
            ->where('cabinet_id', $cabinetId)
            ->where('client_id', $client->id)
            ->firstOrFail();

        return [$client, $dossier];
    }

    /**
     * @param  array<int, array{description: string, quantite: string, prix_unitaire: string, total_ligne_ht: string}>  $lignes
     */
    private function syncLines(Facture $facture, array $lignes): void
    {
        $facture->lignes()->delete();

        foreach ($lignes as $ligne) {
            $facture->lignes()->create([
                ...$ligne,
                'cabinet_id' => $facture->cabinet_id,
            ]);
        }
    }

    private function record(string $action, Facture $facture, User $actor): void
    {
        $this->auditLogService->record(
            action: $action,
            user: $actor,
            cabinet: $facture->cabinet,
            auditable: $facture,
        );
    }

    private function ensureActorHasCabinet(User $actor): void
    {
        if ($actor->cabinet_id !== null) {
            return;
        }

        throw ValidationException::withMessages([
            'cabinet' => __('Une facture doit etre creee depuis un cabinet actif.'),
        ]);
    }
}
