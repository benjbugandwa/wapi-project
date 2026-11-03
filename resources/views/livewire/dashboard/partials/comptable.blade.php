<div class="space-y-8">
    {{-- Boutons d'émission rapide --}}
    <div class="flex flex-wrap items-center justify-between gap-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <div>
            <h2 class="text-base font-semibold text-gray-900">{{ __('Actions comptables rapides') }}</h2>
            <p class="text-xs text-gray-500">{{ __('Émission de factures, encaissement de paiements et suivi des recouvrements') }}</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('factures.index') }}" wire:navigate class="inline-flex items-center gap-1.5 rounded-lg bg-gray-900 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-gray-800">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                {{ __('Créer une facture') }}
            </a>
            <a href="{{ route('paiements.index') }}" wire:navigate class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                {{ __('Enregistrer un paiement') }}
            </a>
            <a href="{{ route('comptabilite.rapports') }}" wire:navigate class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-4 py-2 text-xs font-semibold text-gray-700 shadow-sm hover:bg-gray-50">
                <svg class="h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                {{ __('Rapports & Soldes clients') }}
            </a>
        </div>
    </div>

    {{-- KPIs Comptabilité --}}
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        {{-- Total facturé du mois --}}
        <div class="overflow-hidden rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
            <dt class="truncate text-sm font-medium text-gray-500">{{ __('Facturé ce mois') }}</dt>
            <dd class="mt-2 text-2xl font-bold tracking-tight text-gray-900">
                {{ number_format($totalFactureMois, 2, ',', ' ') }}
            </dd>
            <div class="mt-3 flex items-center text-xs text-gray-500">
                <span>{{ now()->translatedFormat('F Y') }}</span>
            </div>
        </div>

        {{-- Total encaissé du mois --}}
        <div class="overflow-hidden rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
            <dt class="truncate text-sm font-medium text-gray-500">{{ __('Encaissé ce mois') }}</dt>
            <dd class="mt-2 text-2xl font-bold tracking-tight text-emerald-600">
                {{ number_format($totalEncaisseMois, 2, ',', ' ') }}
            </dd>
            <div class="mt-3 flex items-center text-xs text-gray-500">
                <span>{{ __('Paiements reçus') }}</span>
            </div>
        </div>

        {{-- Taux de recouvrement --}}
        <div class="overflow-hidden rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
            <dt class="truncate text-sm font-medium text-gray-500">{{ __('Taux d encaissement') }}</dt>
            <dd class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl font-bold tracking-tight text-gray-900">{{ $tauxRecouvrement }}%</span>
                <span class="text-xs text-gray-500">{{ __('du mois') }}</span>
            </dd>
            <div class="mt-3 w-full bg-gray-100 rounded-full h-2">
                <div class="bg-indigo-600 h-2 rounded-full" style="width: {{ min(100, $tauxRecouvrement) }}%"></div>
            </div>
        </div>

        {{-- Alertes retards > 30 jours --}}
        <div class="overflow-hidden rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
            <dt class="truncate text-sm font-medium text-gray-500">{{ __('Retards > 30 jours') }}</dt>
            <dd class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl font-bold tracking-tight text-red-600">{{ $facturesRetard30Jours->count() }}</span>
                <span class="rounded-full bg-red-50 px-2 py-0.5 text-xs font-semibold text-red-700">
                    {{ __('Critique') }}
                </span>
            </dd>
            <div class="mt-3 text-xs text-red-600">
                {{ number_format((float) $facturesRetard30Jours->sum(fn ($f) => $f->remainingAmount()), 2, ',', ' ') }} {{ __('en souffrance') }}
            </div>
        </div>
    </div>

    {{-- Tableau d'alertes : Retards de paiement > 30 jours --}}
    <div class="overflow-hidden rounded-xl border border-red-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-red-100 bg-red-50/50 px-6 py-4">
            <div class="flex items-center gap-3">
                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-red-100 text-red-700">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </span>
                <div>
                    <h2 class="text-base font-semibold text-red-900">{{ __('Alertes retards de paiement supérieurs à 30 jours') }}</h2>
                    <p class="text-xs text-red-700">{{ __('Factures échues nécessitant une relance contentieuse ou un suivi immédiat') }}</p>
                </div>
            </div>
            <a href="{{ route('comptabilite.rapports') }}" wire:navigate class="rounded-md border border-red-300 bg-white px-3 py-1.5 text-xs font-medium text-red-700 hover:bg-red-50">
                {{ __('Rapport complet') }} &rarr;
            </a>
        </div>

        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                <tr>
                    <th class="px-6 py-3">{{ __('Facture') }}</th>
                    <th class="px-6 py-3">{{ __('Client') }}</th>
                    <th class="px-6 py-3">{{ __('Échéance') }}</th>
                    <th class="px-6 py-3">{{ __('Retard') }}</th>
                    <th class="px-6 py-3">{{ __('Total TTC') }}</th>
                    <th class="px-6 py-3">{{ __('Reste dû') }}</th>
                    <th class="px-6 py-3 text-right">{{ __('Action') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($facturesRetard30Jours as $facture)
                    @php
                        $retardJours = (int) $facture->date_echeance->diffInDays(now()->startOfDay());
                    @endphp
                    <tr class="hover:bg-red-50/20">
                        <td class="px-6 py-4">
                            <a href="{{ route('factures.show', $facture) }}" wire:navigate class="font-semibold text-gray-900 hover:text-indigo-600">
                                {{ $facture->numero_facture }}
                            </a>
                        </td>
                        <td class="px-6 py-4 text-gray-700">
                            {{ $facture->client->nom }} {{ $facture->client->prenom }}
                        </td>
                        <td class="px-6 py-4 text-gray-600">
                            {{ $facture->date_echeance->format('d/m/Y') }}
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-semibold text-red-800">
                                +{{ $retardJours }} {{ __('jours') }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-gray-900 font-medium">
                            {{ number_format((float) $facture->montant_ttc, 2, ',', ' ') }}
                        </td>
                        <td class="px-6 py-4 font-bold text-red-700">
                            {{ number_format((float) $facture->remainingAmount(), 2, ',', ' ') }}
                        </td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('factures.show', $facture) }}" wire:navigate class="rounded-md bg-gray-900 px-2.5 py-1.5 text-xs font-medium text-white hover:bg-gray-800">
                                {{ __('Voir / Relancer') }}
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-sm text-gray-500">
                            <div class="flex flex-col items-center justify-center">
                                <svg class="h-8 w-8 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span class="mt-2 font-medium text-gray-700">{{ __('Excellente nouvelle !') }}</span>
                                <span class="text-xs text-gray-500">{{ __('Aucune facture en retard de plus de 30 jours.') }}</span>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
