<div class="space-y-8">
    {{-- KPIs Plateforme SaaS --}}
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        {{-- Total Cabinets --}}
        <div class="overflow-hidden rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
            <dt class="truncate text-sm font-medium text-gray-500">{{ __('Cabinets inscrits') }}</dt>
            <dd class="mt-2 flex items-baseline justify-between">
                <span class="text-3xl font-bold tracking-tight text-gray-900">{{ $totalCabinets }}</span>
                <span class="rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-medium text-indigo-700">
                    {{ __('Tenants') }}
                </span>
            </dd>
            <div class="mt-3">
                <a href="{{ route('admin.cabinets.create') }}" wire:navigate class="text-xs font-medium text-indigo-600 hover:text-indigo-800">
                    + {{ __('Nouveau cabinet') }} &rarr;
                </a>
            </div>
        </div>

        {{-- Abonnements Actifs --}}
        <div class="overflow-hidden rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
            <dt class="truncate text-sm font-medium text-gray-500">{{ __('Abonnements actifs') }}</dt>
            <dd class="mt-2 flex items-baseline justify-between">
                <span class="text-3xl font-bold tracking-tight text-emerald-600">{{ $cabinetsActifs }}</span>
                @if ($cabinetsSuspendus > 0)
                    <span class="rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-bold text-amber-700">
                        {{ $cabinetsSuspendus }} {{ __('suspendu(s)') }}
                    </span>
                @else
                    <span class="rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700">
                        100% {{ __('opérationnels') }}
                    </span>
                @endif
            </dd>
            <div class="mt-3 text-xs text-gray-500">
                {{ __('Cabinets avec accès complet') }}
            </div>
        </div>

        {{-- Stockage global utilisé --}}
        <div class="overflow-hidden rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
            <dt class="truncate text-sm font-medium text-gray-500">{{ __('Stockage total consommé') }}</dt>
            <dd class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl font-bold tracking-tight text-gray-900">
                    {{ $globalStorageFormatted }}
                </span>
                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600">
                    {{ $totalDocumentsCount }} {{ __('fichiers') }}
                </span>
            </dd>
            <div class="mt-3 text-xs text-gray-500">
                {{ __('Volume persistant / Documents clients') }}
            </div>
        </div>

        {{-- Utilisateurs du SaaS --}}
        <div class="overflow-hidden rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
            <dt class="truncate text-sm font-medium text-gray-500">{{ __('Utilisateurs totaux') }}</dt>
            <dd class="mt-2 flex items-baseline justify-between">
                <span class="text-3xl font-bold tracking-tight text-gray-900">{{ $totalUsersCount }}</span>
                <span class="text-xs text-gray-500">~{{ $averageUsersPerCabinet }} {{ __('par cabinet') }}</span>
            </dd>
            <div class="mt-3 text-xs text-gray-500">
                {{ __('Avocats, comptables, juristes') }}
            </div>
        </div>
    </div>

    {{-- Suivi des Quotas de Stockage Documents par Cabinet --}}
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-100 px-6 py-4">
            <h2 class="text-base font-semibold text-gray-900">{{ __('Quotas et consommation de stockage des documents') }}</h2>
            <p class="text-xs text-gray-500">{{ __('Surveillance du volume de pièces jointes par cabinet (Quota standard alloué : 10 Go par tenant)') }}</p>
        </div>

        <div class="divide-y divide-gray-100">
            @forelse ($cabinetsStorageUsage as $storageInfo)
                <div class="p-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 hover:bg-gray-50/50">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-3">
                            <span class="font-bold text-gray-900 text-sm">{{ $storageInfo['denomination'] }}</span>
                            <span class="text-xs text-gray-500">({{ $storageInfo['documents_count'] }} {{ __('documents') }})</span>
                        </div>
                        <div class="mt-2 w-full max-w-lg bg-gray-100 rounded-full h-2.5 overflow-hidden">
                            <div
                                class="h-2.5 rounded-full {{ $storageInfo['percentage'] >= 90 ? 'bg-red-600' : ($storageInfo['percentage'] >= 70 ? 'bg-amber-500' : 'bg-emerald-500') }}"
                                style="width: {{ min(100, $storageInfo['percentage']) }}%"
                            ></div>
                        </div>
                    </div>

                    <div class="flex items-center gap-6 shrink-0">
                        <div class="text-right">
                            <div class="text-sm font-semibold text-gray-900">{{ $storageInfo['used_formatted'] }} / {{ $storageInfo['quota_formatted'] }}</div>
                            <div class="text-xs font-medium {{ $storageInfo['percentage'] >= 90 ? 'text-red-600' : ($storageInfo['percentage'] >= 70 ? 'text-amber-600' : 'text-gray-500') }}">
                                {{ $storageInfo['percentage'] }}% {{ __('du quota utilisé') }}
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="p-6 text-center text-sm text-gray-500">{{ __('Aucun cabinet enregistré.') }}</div>
            @endforelse
        </div>
    </div>

    {{-- Liste des Cabinets et Gestion des Abonnements --}}
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
            <div>
                <h2 class="text-base font-semibold text-gray-900">{{ __('Cabinets & État des abonnements') }}</h2>
                <p class="text-xs text-gray-500">{{ __('Supervision des accès et suspension administrative ou pour impayé') }}</p>
            </div>
            <a href="{{ route('admin.cabinets.index') }}" wire:navigate class="rounded-md border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50">
                {{ __('Gérer tous les cabinets') }} &rarr;
            </a>
        </div>

        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                <tr>
                    <th class="px-6 py-3">{{ __('Cabinet') }}</th>
                    <th class="px-6 py-3">{{ __('Répondant') }}</th>
                    <th class="px-6 py-3">{{ __('Membres') }}</th>
                    <th class="px-6 py-3">{{ __('Dossiers') }}</th>
                    <th class="px-6 py-3">{{ __('Statut Abonnement') }}</th>
                    <th class="px-6 py-3 text-right">{{ __('Action Abonnement') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($cabinets as $cabinet)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="font-semibold text-gray-900">{{ $cabinet->denomination }}</div>
                            <div class="text-xs text-gray-500">{{ $cabinet->adresse_cabinet }}</div>
                        </td>
                        <td class="px-6 py-4 text-gray-700">
                            <div>{{ $cabinet->nom_repondant }}</div>
                            <div class="text-xs text-gray-500">{{ $cabinet->telephone_repondant }}</div>
                        </td>
                        <td class="px-6 py-4 text-gray-900 font-medium">
                            {{ $cabinet->users_count }}
                        </td>
                        <td class="px-6 py-4 text-gray-900 font-medium">
                            {{ $cabinet->dossiers_count }}
                        </td>
                        <td class="px-6 py-4">
                            @if ($cabinet->isActive())
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-600"></span>
                                    {{ __('Abonnement Actif') }}
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">
                                    <span class="h-1.5 w-1.5 rounded-full bg-amber-600"></span>
                                    {{ __('Suspendu') }}
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            @if ($cabinet->isActive())
                                <button
                                    type="button"
                                    wire:click="suspendCabinet('{{ $cabinet->id }}')"
                                    wire:confirm="{{ __('Voulez-vous vraiment suspendre l abonnement et l accès de ce cabinet ?') }}"
                                    wire:loading.attr="disabled"
                                    class="rounded-md bg-amber-50 px-2.5 py-1.5 text-xs font-medium text-amber-800 hover:bg-amber-100"
                                >
                                    {{ __('Suspendre') }}
                                </button>
                            @else
                                <button
                                    type="button"
                                    wire:click="activateCabinet('{{ $cabinet->id }}')"
                                    wire:confirm="{{ __('Voulez-vous réactiver l abonnement de ce cabinet ?') }}"
                                    wire:loading.attr="disabled"
                                    class="rounded-md bg-emerald-50 px-2.5 py-1.5 text-xs font-medium text-emerald-800 hover:bg-emerald-100"
                                >
                                    {{ __('Réactiver') }}
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500">
                            {{ __('Aucun cabinet enregistré pour le moment.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
