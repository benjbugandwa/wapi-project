<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\FactureStatusEnum;
use App\Models\Facture;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ComptabiliteReportService
{
    public function overdueInvoices(User $actor): LengthAwarePaginator
    {
        return Facture::query()
            ->with(['client', 'dossier'])
            ->when(! $actor->isSystemAdmin(), fn (Builder $query) => $query->where('cabinet_id', $actor->cabinet_id))
            ->where('date_echeance', '<', now()->toDateString())
            ->whereNotIn('statut', [FactureStatusEnum::Soldee->value, FactureStatusEnum::Annulee->value])
            ->orderBy('date_echeance')
            ->paginate(15, ['*'], 'souffrancePage');
    }

    public function clientSituation(User $actor, ?string $clientId): LengthAwarePaginator
    {
        return Facture::query()
            ->with(['client', 'dossier', 'paiements'])
            ->when(! $actor->isSystemAdmin(), fn (Builder $query) => $query->where('cabinet_id', $actor->cabinet_id))
            ->when($clientId, fn (Builder $query) => $query->where('client_id', $clientId))
            ->orderByDesc('date_emission')
            ->paginate(15, ['*'], 'situationPage');
    }
}
