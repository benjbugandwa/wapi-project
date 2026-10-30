<?php

use App\Enums\CabinetStatus;
use App\Enums\DossierStatusEnum;
use App\Enums\DossierUserRoleEnum;
use App\Enums\EvenementStatusEnum;
use App\Enums\EvenementTypeEnum;
use App\Enums\FactureStatusEnum;
use App\Enums\RoleEnum;
use App\Models\Cabinet;
use App\Models\Document;
use App\Models\Dossier;
use App\Models\Evenement;
use App\Models\Facture;
use App\Models\Paiement;
use App\Models\User;
use App\Services\CabinetService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {
    public string $currentRole = '';

    public function mount(): void
    {
        $user = auth()->user();
        $this->currentRole = $user->primaryRole()->value;
    }

    public function switchRole(string $role): void
    {
        $user = auth()->user();

        if ($user->isSystemAdmin() || $user->hasRole($role) || $user->hasRole(RoleEnum::Admin->value)) {
            $this->currentRole = $role;
        }
    }

    public function suspendCabinet(string $cabinetId, CabinetService $cabinetService): void
    {
        abort_unless(auth()->user()->isSystemAdmin(), 403);

        $cabinet = Cabinet::withoutGlobalScopes()->findOrFail($cabinetId);
        $cabinetService->suspend($cabinet, auth()->user());

        notify()->success(__('Cabinet suspendu avec succès.'), __('Succès'));
    }

    public function activateCabinet(string $cabinetId, CabinetService $cabinetService): void
    {
        abort_unless(auth()->user()->isSystemAdmin(), 403);

        $cabinet = Cabinet::withoutGlobalScopes()->findOrFail($cabinetId);
        $cabinetService->activate($cabinet, auth()->user());

        notify()->success(__('Cabinet réactivé avec succès.'), __('Succès'));
    }

    public function with(): array
    {
        $user = auth()->user();

        // 1. Données Avocat & Collaborateurs
        $dossiersActifs = $user->assignedDossiers()
            ->whereNotIn('statut_dossier', [DossierStatusEnum::Clos->value, DossierStatusEnum::Archive->value])
            ->with(['client'])
            ->orderByDesc('dossiers.updated_at')
            ->get();

        $audiences15Jours = Evenement::query()
            ->where('type_evenement', EvenementTypeEnum::Audience->value)
            ->where('statut_evenement', '!=', EvenementStatusEnum::Annule->value)
            ->whereBetween('date_debut', [now()->startOfDay(), now()->addDays(15)->endOfDay()])
            ->where(function (Builder $q) use ($user) {
                $q->where('created_by', $user->id)
                    ->orWhereHas('participants', fn (Builder $p) => $p->where('users.id', $user->id))
                    ->orWhereHas('dossier.assignedUsers', fn (Builder $du) => $du->where('users.id', $user->id));
            })
            ->with(['dossier.client'])
            ->orderBy('date_debut')
            ->get();

        $tachesUrgentes = Evenement::query()
            ->where('type_evenement', EvenementTypeEnum::DateButoir->value)
            ->where('statut_evenement', '!=', EvenementStatusEnum::Annule->value)
            ->where('date_debut', '<=', now()->addDays(7)->endOfDay())
            ->where(function (Builder $q) use ($user) {
                $q->where('created_by', $user->id)
                    ->orWhereHas('participants', fn (Builder $p) => $p->where('users.id', $user->id))
                    ->orWhereHas('dossier.assignedUsers', fn (Builder $du) => $du->where('users.id', $user->id));
            })
            ->with(['dossier.client'])
            ->orderBy('date_debut')
            ->get();

        // 2. Données Comptable
        $totalFactureMois = (float) Facture::query()
            ->whereBetween('date_emission', [now()->startOfMonth(), now()->endOfMonth()])
            ->where('statut', '!=', FactureStatusEnum::Annulee->value)
            ->sum('montant_ttc');

        $totalEncaisseMois = (float) Paiement::query()
            ->whereBetween('date_paiement', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('montant_paye');

        $tauxRecouvrement = $totalFactureMois > 0
            ? min(100, round(($totalEncaisseMois / $totalFactureMois) * 100, 1))
            : 0;

        $facturesRetard30Jours = Facture::query()
            ->with(['client', 'dossier', 'paiements'])
            ->where('date_echeance', '<', now()->subDays(30)->toDateString())
            ->whereNotIn('statut', [FactureStatusEnum::Soldee->value, FactureStatusEnum::Annulee->value])
            ->orderBy('date_echeance')
            ->get();

        // 3. Données Admin Cabinet
        $collaborateurs = User::query()
            ->where('cabinet_id', $user->cabinet_id)
            ->where('is_active', true)
            ->with('roles')
            ->withCount([
                'assignedDossiers as active_dossiers_count' => fn (Builder $q) =>
                    $q->whereNotIn('statut_dossier', [DossierStatusEnum::Clos->value, DossierStatusEnum::Archive->value]),
                'assignedDossiers as dossiers_responsable_count' => fn (Builder $q) =>
                    $q->whereNotIn('statut_dossier', [DossierStatusEnum::Clos->value, DossierStatusEnum::Archive->value])
                        ->where('dossier_user.role_dossier', DossierUserRoleEnum::Responsable->value),
                'assignedDossiers as dossiers_collaborateur_count' => fn (Builder $q) =>
                    $q->whereNotIn('statut_dossier', [DossierStatusEnum::Clos->value, DossierStatusEnum::Archive->value])
                        ->where('dossier_user.role_dossier', DossierUserRoleEnum::Collaborateur->value),
            ])
            ->get();

        $collaborateursActifsCount = $collaborateurs->count();
        $totalDossiersActifs = Dossier::query()
            ->whereNotIn('statut_dossier', [DossierStatusEnum::Clos->value, DossierStatusEnum::Archive->value])
            ->count();

        $collaborateursOccupes = $collaborateurs->filter(fn (User $u) => $u->active_dossiers_count > 0)->count();
        $tauxOccupation = $collaborateursActifsCount > 0
            ? round(($collaborateursOccupes / $collaborateursActifsCount) * 100, 1)
            : 0;
        $moyenneDossiers = $collaborateursActifsCount > 0
            ? round($totalDossiersActifs / $collaborateursActifsCount, 1)
            : 0;

        $dossiersSansResponsable = Dossier::query()
            ->whereNotIn('statut_dossier', [DossierStatusEnum::Clos->value, DossierStatusEnum::Archive->value])
            ->whereDoesntHave('assignedUsers', fn (Builder $q) =>
                $q->where('dossier_user.role_dossier', DossierUserRoleEnum::Responsable->value)
            )
            ->with(['client'])
            ->orderByDesc('created_at')
            ->get();

        // 4. Données Admin Système
        $cabinets = Cabinet::withoutGlobalScopes()
            ->withCount(['users', 'dossiers', 'documents'])
            ->latest()
            ->get();

        $totalCabinets = $cabinets->count();
        $cabinetsActifs = $cabinets->where('statut_cabinet', CabinetStatus::Active)->count();
        $cabinetsSuspendus = $cabinets->where('statut_cabinet', CabinetStatus::Suspended)->count();

        $standardQuotaBytes = 10 * 1024 * 1024 * 1024; // 10 Go
        $totalStorageBytes = (int) Document::withoutGlobalScopes()->sum('taille_octets');
        $totalDocumentsCount = Document::withoutGlobalScopes()->count();
        $totalUsersCount = User::withoutGlobalScopes()->count();
        $averageUsersPerCabinet = $totalCabinets > 0 ? round($totalUsersCount / $totalCabinets, 1) : 0;

        $globalStorageFormatted = $totalStorageBytes >= 1024 * 1024 * 1024
            ? number_format($totalStorageBytes / (1024 * 1024 * 1024), 2) . ' Go'
            : number_format($totalStorageBytes / (1024 * 1024), 1) . ' Mo';

        $storageByCabinet = Document::withoutGlobalScopes()
            ->selectRaw('cabinet_id, sum(taille_octets) as total_octets')
            ->groupBy('cabinet_id')
            ->pluck('total_octets', 'cabinet_id')
            ->all();

        $cabinetsStorageUsage = $cabinets->map(function (Cabinet $cab) use ($storageByCabinet, $standardQuotaBytes): array {
            $usedBytes = (int) ($storageByCabinet[$cab->id] ?? 0);
            $percentage = $standardQuotaBytes > 0 ? round(($usedBytes / $standardQuotaBytes) * 100, 1) : 0;
            $usedFormatted = $usedBytes >= 1024 * 1024 * 1024
                ? number_format($usedBytes / (1024 * 1024 * 1024), 2) . ' Go'
                : number_format($usedBytes / (1024 * 1024), 1) . ' Mo';

            return [
                'id' => $cab->id,
                'denomination' => $cab->denomination,
                'documents_count' => $cab->documents_count,
                'used_bytes' => $usedBytes,
                'used_formatted' => $usedFormatted,
                'quota_formatted' => '10 Go',
                'percentage' => $percentage,
            ];
        });

        return [
            // Avocat
            'dossiersActifs' => $dossiersActifs,
            'audiences15Jours' => $audiences15Jours,
            'tachesUrgentes' => $tachesUrgentes,

            // Comptable
            'totalFactureMois' => $totalFactureMois,
            'totalEncaisseMois' => $totalEncaisseMois,
            'tauxRecouvrement' => $tauxRecouvrement,
            'facturesRetard30Jours' => $facturesRetard30Jours,

            // Admin Cabinet
            'collaborateurs' => $collaborateurs,
            'collaborateursActifsCount' => $collaborateursActifsCount,
            'collaborateursOccupes' => $collaborateursOccupes,
            'totalDossiersActifs' => $totalDossiersActifs,
            'tauxOccupation' => $tauxOccupation,
            'moyenneDossiers' => $moyenneDossiers,
            'dossiersSansResponsable' => $dossiersSansResponsable,

            // Admin Système
            'cabinets' => $cabinets,
            'totalCabinets' => $totalCabinets,
            'cabinetsActifs' => $cabinetsActifs,
            'cabinetsSuspendus' => $cabinetsSuspendus,
            'totalDocumentsCount' => $totalDocumentsCount,
            'totalUsersCount' => $totalUsersCount,
            'averageUsersPerCabinet' => $averageUsersPerCabinet,
            'globalStorageFormatted' => $globalStorageFormatted,
            'cabinetsStorageUsage' => $cabinetsStorageUsage,
        ];
    }
}; ?>

<div class="py-8">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        {{-- En-tête contextuel et sélecteur de rôle --}}
        @include('livewire.dashboard.partials.header')

        {{-- Vue spécialisée selon le rôle actif --}}
        @if ($currentRole === \App\Enums\RoleEnum::SystemAdmin->value)
            @include('livewire.dashboard.partials.admin-systeme')
        @elseif ($currentRole === \App\Enums\RoleEnum::Admin->value)
            @include('livewire.dashboard.partials.admin-cabinet')
        @elseif ($currentRole === \App\Enums\RoleEnum::Comptable->value)
            @include('livewire.dashboard.partials.comptable')
        @else
            @include('livewire.dashboard.partials.avocat')
        @endif
    </div>
</div>
