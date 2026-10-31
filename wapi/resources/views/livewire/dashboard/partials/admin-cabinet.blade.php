<div class="space-y-8">
    {{-- KPIs Admin Cabinet --}}
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        {{-- Collaborateurs actifs --}}
        <div class="overflow-hidden rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
            <dt class="truncate text-sm font-medium text-gray-500">{{ __('Collaborateurs actifs') }}</dt>
            <dd class="mt-2 flex items-baseline justify-between">
                <span class="text-3xl font-bold tracking-tight text-gray-900">{{ $collaborateursActifsCount }}</span>
                <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700">
                    {{ __('En service') }}
                </span>
            </dd>
            <div class="mt-3">
                <a href="{{ route('admin.users.index') }}" wire:navigate class="text-xs font-medium text-indigo-600 hover:text-indigo-800">
                    {{ __('Gérer les utilisateurs') }} &rarr;
                </a>
            </div>
        </div>

        {{-- Taux d'occupation --}}
        <div class="overflow-hidden rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
            <dt class="truncate text-sm font-medium text-gray-500">{{ __('Taux d occupation') }}</dt>
            <dd class="mt-2 flex items-baseline justify-between">
                <span class="text-3xl font-bold tracking-tight text-gray-900">{{ $tauxOccupation }}%</span>
                <span class="text-xs text-gray-500">
                    {{ $collaborateursOccupes }} / {{ $collaborateursActifsCount }} {{ __('actifs') }}
                </span>
            </dd>
            <div class="mt-3 w-full bg-gray-100 rounded-full h-2">
                <div class="bg-indigo-600 h-2 rounded-full" style="width: {{ min(100, $tauxOccupation) }}%"></div>
            </div>
        </div>

        {{-- Dossiers actifs totaux --}}
        <div class="overflow-hidden rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
            <dt class="truncate text-sm font-medium text-gray-500">{{ __('Dossiers actifs du cabinet') }}</dt>
            <dd class="mt-2 flex items-baseline justify-between">
                <span class="text-3xl font-bold tracking-tight text-gray-900">{{ $totalDossiersActifs }}</span>
                <span class="text-xs text-gray-500">~{{ $moyenneDossiers }} {{ __('par juriste') }}</span>
            </dd>
            <div class="mt-3">
                <a href="{{ route('dossiers.index') }}" wire:navigate class="text-xs font-medium text-indigo-600 hover:text-indigo-800">
                    {{ __('Explorer les dossiers') }} &rarr;
                </a>
            </div>
        </div>

        {{-- Dossiers sans avocat responsable --}}
        <div class="overflow-hidden rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
            <dt class="truncate text-sm font-medium text-gray-500">{{ __('Sans avocat responsable') }}</dt>
            <dd class="mt-2 flex items-baseline justify-between">
                <span class="text-3xl font-bold tracking-tight {{ $dossiersSansResponsable->count() > 0 ? 'text-red-600' : 'text-gray-900' }}">
                    {{ $dossiersSansResponsable->count() }}
                </span>
                @if ($dossiersSansResponsable->count() > 0)
                    <span class="rounded-full bg-red-100 px-2 py-0.5 text-xs font-bold text-red-800 animate-pulse">
                        {{ __('Alerte') }}
                    </span>
                @else
                    <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700">
                        {{ __('Conforme') }}
                    </span>
                @endif
            </dd>
            <div class="mt-3 text-xs text-gray-500">
                {{ __('Dossiers orphelins de direction') }}
            </div>
        </div>
    </div>

    {{-- ALERTE CRITIQUE : Dossiers actifs sans avocat responsable --}}
    @if ($dossiersSansResponsable->isNotEmpty())
        <div class="overflow-hidden rounded-xl border-2 border-red-300 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-red-100 bg-red-50 px-6 py-4">
                <div class="flex items-center gap-3">
                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-red-200 text-red-800">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </span>
                    <div>
                        <h2 class="text-base font-bold text-red-900">{{ __('Action requise : Dossiers sans avocat responsable désigné') }}</h2>
                        <p class="text-xs text-red-700">{{ __('Chaque dossier actif doit légalement et déontologiquement être conduit par un avocat responsable.') }}</p>
                    </div>
                </div>
                <span class="rounded-full bg-red-200 px-3 py-1 text-xs font-bold text-red-900">
                    {{ $dossiersSansResponsable->count() }} {{ __('dossier(s) à régulariser') }}
                </span>
            </div>

            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-6 py-3">{{ __('Dossier') }}</th>
                        <th class="px-6 py-3">{{ __('Client') }}</th>
                        <th class="px-6 py-3">{{ __('Ouverture') }}</th>
                        <th class="px-6 py-3">{{ __('Statut actuel') }}</th>
                        <th class="px-6 py-3 text-right">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($dossiersSansResponsable as $dossierOrphelin)
                        <tr class="hover:bg-red-50/30">
                            <td class="px-6 py-4 font-semibold text-gray-900">
                                {{ $dossierOrphelin->numero_reference }} - {{ $dossierOrphelin->titre_dossier }}
                            </td>
                            <td class="px-6 py-4 text-gray-700">
                                {{ $dossierOrphelin->client->nom }} {{ $dossierOrphelin->client->prenom }}
                            </td>
                            <td class="px-6 py-4 text-gray-500 text-xs">
                                {{ $dossierOrphelin->date_ouverture->format('d/m/Y') }} ({{ $dossierOrphelin->ageInDays() }} {{ __('jours') }})
                            </td>
                            <td class="px-6 py-4">
                                <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800">
                                    {{ $dossierOrphelin->statut_dossier->label() }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('dossiers.show', $dossierOrphelin) }}" wire:navigate class="rounded-md bg-red-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-red-700">
                                    {{ __('Affecter un responsable') }} &rarr;
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- Charge de travail et collaborateurs actifs --}}
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
            <div>
                <h2 class="text-base font-semibold text-gray-900">{{ __('Charge de travail des collaborateurs du cabinet') }}</h2>
                <p class="text-xs text-gray-500">{{ __('Répartition des dossiers et occupation de l équipe') }}</p>
            </div>
            @can('viewAny', \App\Models\User::class)
                <a href="{{ route('admin.users.index') }}" wire:navigate class="rounded-md bg-gray-900 px-3.5 py-2 text-xs font-medium text-white hover:bg-gray-800">
                    {{ __('Voir les collaborateurs') }}
                </a>
            @endcan
        </div>

        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                <tr>
                    <th class="px-6 py-3">{{ __('Collaborateur') }}</th>
                    <th class="px-6 py-3">{{ __('Rôle principal') }}</th>
                    <th class="px-6 py-3">{{ __('Responsable') }}</th>
                    <th class="px-6 py-3">{{ __('Collaborateur') }}</th>
                    <th class="px-6 py-3">{{ __('Total dossiers actifs') }}</th>
                    <th class="px-6 py-3 text-right">{{ __('Dernière activité') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($collaborateurs as $collaborateur)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="font-semibold text-gray-900">{{ $collaborateur->nom }}</div>
                            <div class="text-xs text-gray-500">{{ $collaborateur->email }}</div>
                        </td>
                        <td class="px-6 py-4 text-gray-700">
                            {{ $collaborateur->roles->first()?->name ? \App\Enums\RoleEnum::tryFrom($collaborateur->roles->first()->name)?->label() : __('Membre') }}
                        </td>
                        <td class="px-6 py-4 text-gray-900 font-medium">
                            {{ $collaborateur->dossiers_responsable_count }}
                        </td>
                        <td class="px-6 py-4 text-gray-600">
                            {{ $collaborateur->dossiers_collaborateur_count }}
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-gray-900">{{ $collaborateur->active_dossiers_count }}</span>
                                @if ($collaborateur->active_dossiers_count > 0)
                                    <span class="inline-block h-2 w-2 rounded-full bg-emerald-500"></span>
                                @else
                                    <span class="inline-block h-2 w-2 rounded-full bg-gray-300"></span>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 text-right text-xs text-gray-500">
                            {{ $collaborateur->last_login_at?->diffForHumans() ?? __('Jamais') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500">
                            {{ __('Aucun collaborateur trouvé pour ce cabinet.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
