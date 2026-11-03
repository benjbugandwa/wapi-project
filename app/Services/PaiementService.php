<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\CreatePaiementData;
use App\Enums\FactureStatusEnum;
use App\Models\Facture;
use App\Models\Paiement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaiementService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {}

    public function create(CreatePaiementData $data, User $actor): Paiement
    {
        $this->ensureActorHasCabinet($actor);

        return DB::transaction(function () use ($data, $actor): Paiement {
            $facture = Facture::query()
                ->whereKey($data->factureId)
                ->where('cabinet_id', $actor->cabinet_id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->ensurePayable($facture, $data->montantPaye);

            $paiement = Paiement::query()->create([
                'cabinet_id' => $facture->cabinet_id,
                'facture_id' => $facture->id,
                'montant_paye' => $data->montantPaye,
                'date_paiement' => $data->datePaiement,
                'methode_paiement' => $data->methodePaiement,
                'reference_transaction' => $data->referenceTransaction,
                'created_by' => $actor->id,
            ]);

            $this->refreshFactureStatus($facture);

            $this->auditLogService->record(
                action: 'paiement.created',
                user: $actor,
                cabinet: $facture->cabinet,
                auditable: $paiement,
                metadata: ['facture_id' => $facture->id],
            );

            return $paiement->refresh();
        });
    }

    private function ensurePayable(Facture $facture, string $montantPaye): void
    {
        if ($facture->isCancelled()) {
            throw ValidationException::withMessages([
                'facture_id' => __('Une facture annulee ne peut pas recevoir de paiement.'),
            ]);
        }

        if ($facture->isPaid()) {
            throw ValidationException::withMessages([
                'facture_id' => __('Une facture soldee ne peut plus recevoir de paiement.'),
            ]);
        }

        if ((float) $montantPaye <= 0) {
            throw ValidationException::withMessages([
                'montant_paye' => __('Le montant paye doit etre superieur a zero.'),
            ]);
        }

        if ((float) $montantPaye > (float) $facture->remainingAmount()) {
            throw ValidationException::withMessages([
                'montant_paye' => __('Le paiement ne peut pas depasser le montant restant du.'),
            ]);
        }
    }

    private function refreshFactureStatus(Facture $facture): void
    {
        $paidAmount = (float) $facture->paidAmount();
        $total = (float) $facture->montant_ttc;

        $facture->forceFill([
            'statut' => $paidAmount >= $total
                ? FactureStatusEnum::Soldee
                : FactureStatusEnum::PayeePartiellement,
        ])->save();
    }

    private function ensureActorHasCabinet(User $actor): void
    {
        if ($actor->cabinet_id !== null) {
            return;
        }

        throw ValidationException::withMessages([
            'cabinet' => __('Un paiement doit etre enregistre depuis un cabinet actif.'),
        ]);
    }
}
