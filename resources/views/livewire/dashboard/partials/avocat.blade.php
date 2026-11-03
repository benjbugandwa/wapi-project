<div class="space-y-8">
    {{-- KPIs Avocat --}}
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
        <div class="relative overflow-hidden rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
            <dt class="truncate text-sm font-medium text-gray-500">{{ __('Dossiers actifs en charge') }}</dt>
            <dd class="mt-2 flex items-baseline justify-between">
                <span class="text-3xl font-bold tracking-tight text-gray-900">{{ $dossiersActifs->count() }}</span>
                <span class="rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-medium text-blue-700">
                    {{ $dossiersActifs->where('pivot.role_dossier', \App\Enums\DossierUserRoleEnum::Responsable->value)->count() }} {{ __('responsable') }}
                </span>
            </dd>
            <div class="mt-4">
                <a href="{{ route('dossiers.index') }}" wire:navigate class="text-xs font-medium text-indigo-600 hover:text-indigo-800">
                    {{ __('Voir tous les dossiers') }} &rarr;
                </a>
            </div>
        </div>

        <div class="relative overflow-hidden rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
            <dt class="truncate text-sm font-medium text-gray-500">{{ __('Audiences à 15 jours') }}</dt>
            <dd class="mt-2 flex items-baseline justify-between">
                <span class="text-3xl font-bold tracking-tight text-gray-900">{{ $audiences15Jours->count() }}</span>
                @if ($audiences15Jours->isNotEmpty())
                    <span class="rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-medium text-amber-700">
                        {{ __('Prochaine :') }} {{ $audiences15Jours->first()->date_debut->format('d/m') }}
                    </span>
                @endif
            </dd>
            <div class="mt-4">
                <a href="{{ route('evenements.index') }}" wire:navigate class="text-xs font-medium text-indigo-600 hover:text-indigo-800">
                    {{ __('Consulter l agenda') }} &rarr;
                </a>
            </div>
        </div>

        <div class="relative overflow-hidden rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
            <dt class="truncate text-sm font-medium text-gray-500">{{ __('Tâches & échéances urgentes') }}</dt>
            <dd class="mt-2 flex items-baseline justify-between">
                <span class="text-3xl font-bold tracking-tight text-gray-900">{{ $tachesUrgentes->count() }}</span>
                @if ($tachesUrgentes->count() > 0)
                    <span class="rounded-full bg-red-50 px-2.5 py-0.5 text-xs font-medium text-red-700">
                        {{ __('Dates butoirs prioritaires') }}
                    </span>
                @endif
            </dd>
            <div class="mt-4">
                <span class="text-xs text-gray-500">{{ __('Échéances dans les 7 jours') }}</span>
            </div>
        </div>
    </div>

    {{-- Grille principale : Audiences & Tâches urgentes --}}
    <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
        {{-- Prochaines audiences à 15 jours --}}
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between border-b border-gray-100 pb-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">{{ __('Prochaines audiences (15 jours)') }}</h2>
                    <p class="text-xs text-gray-500">{{ __('Audiences fixées devant les juridictions') }}</p>
                </div>
                <a href="{{ route('evenements.index') }}" wire:navigate class="rounded-md bg-gray-50 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-100">
                    {{ __('Agenda complet') }}
                </a>
            </div>

            <div class="mt-4 divide-y divide-gray-100">
                @forelse ($audiences15Jours as $audience)
                    <div class="py-3.5 flex items-start justify-between gap-4">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <span class="font-medium text-gray-900 text-sm truncate">{{ $audience->titre }}</span>
                                @php
                                    $diffInDays = (int) now()->startOfDay()->diffInDays($audience->date_debut->startOfDay(), false);
                                @endphp
                                @if ($diffInDays === 0)
                                    <span class="rounded bg-red-100 px-1.5 py-0.5 text-[10px] font-semibold text-red-700">{{ __('Aujourd hui') }}</span>
                                @elseif ($diffInDays === 1)
                                    <span class="rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-semibold text-amber-700">{{ __('Demain') }}</span>
                                @else
                                    <span class="rounded bg-gray-100 px-1.5 py-0.5 text-[10px] font-medium text-gray-600">J-{{ $diffInDays }}</span>
                                @endif
                            </div>
                            <div class="mt-1 flex items-center gap-3 text-xs text-gray-500">
                                <span>📅 {{ $audience->date_debut->format('d/m/Y H:i') }}</span>
                                @if ($audience->lieu)
                                    <span>📍 {{ $audience->lieu }}</span>
                                @endif
                                @if ($audience->dossier)
                                    <span class="font-medium text-gray-700">{{ $audience->dossier->numero_reference }}</span>
                                @endif
                            </div>
                        </div>
                        @if ($audience->dossier)
                            <a href="{{ route('dossiers.show', $audience->dossier) }}" wire:navigate class="text-xs font-medium text-indigo-600 hover:text-indigo-800 shrink-0">
                                {{ __('Dossier') }} &rarr;
                            </a>
                        @endif
                    </div>
                @empty
                    <p class="py-6 text-center text-sm text-gray-500">{{ __('Aucune audience programmée dans les 15 prochains jours.') }}</p>
                @endforelse
            </div>
        </div>

        {{-- Tâches et dates butoirs urgentes --}}
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between border-b border-gray-100 pb-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">{{ __('Tâches & Délais butoirs urgents') }}</h2>
                    <p class="text-xs text-gray-500">{{ __('Conclusions, mémoires et échéances de procédure') }}</p>
                </div>
                <span class="rounded-full bg-red-50 px-2.5 py-1 text-xs font-medium text-red-700">
                    {{ $tachesUrgentes->count() }} {{ __('urgente(s)') }}
                </span>
            </div>

            <div class="mt-4 divide-y divide-gray-100">
                @forelse ($tachesUrgentes as $tache)
                    <div class="py-3.5 flex items-start justify-between gap-4">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <span class="font-medium text-gray-900 text-sm truncate">{{ $tache->titre }}</span>
                                @if ($tache->date_debut->isPast())
                                    <span class="rounded bg-red-100 px-1.5 py-0.5 text-[10px] font-bold text-red-700 uppercase">{{ __('En retard') }}</span>
                                @else
                                    <span class="rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-medium text-amber-800">{{ __('Échéance imminente') }}</span>
                                @endif
                            </div>
                            <div class="mt-1 flex items-center gap-3 text-xs text-gray-500">
                                <span>⏰ {{ __('Limite :') }} {{ $tache->date_debut->format('d/m/Y') }}</span>
                                @if ($tache->dossier)
                                    <span>📂 {{ $tache->dossier->numero_reference }} - {{ $tache->dossier->client->nom }}</span>
                                @endif
                            </div>
                        </div>
                        @if ($tache->dossier)
                            <a href="{{ route('dossiers.show', $tache->dossier) }}" wire:navigate class="text-xs font-medium text-indigo-600 hover:text-indigo-800 shrink-0">
                                {{ __('Traiter') }} &rarr;
                            </a>
                        @endif
                    </div>
                @empty
                    <p class="py-6 text-center text-sm text-gray-500">{{ __('Aucune tâche urgente ou date butoir en attente.') }}</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Dossiers actifs en charge --}}
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">{{ __('Mes dossiers actifs en charge') }}</h2>
                <p class="text-xs text-gray-500">{{ __('Dossiers en cours où vous êtes désigné responsable ou collaborateur') }}</p>
            </div>
            <a href="{{ route('dossiers.create') }}" wire:navigate class="rounded-md bg-gray-900 px-3.5 py-2 text-xs font-medium text-white hover:bg-gray-800">
                + {{ __('Nouveau dossier') }}
            </a>
        </div>

        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                <tr>
                    <th class="px-6 py-3">{{ __('Référence / Titre') }}</th>
                    <th class="px-6 py-3">{{ __('Client') }}</th>
                    <th class="px-6 py-3">{{ __('Domaine') }}</th>
                    <th class="px-6 py-3">{{ __('Rôle assigné') }}</th>
                    <th class="px-6 py-3">{{ __('Statut') }}</th>
                    <th class="px-6 py-3 text-right">{{ __('Action') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($dossiersActifs as $dossier)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <a href="{{ route('dossiers.show', $dossier) }}" wire:navigate class="font-semibold text-gray-900 hover:text-indigo-600">
                                {{ $dossier->numero_reference }}
                            </a>
                            <div class="text-xs text-gray-500 truncate max-w-xs">{{ $dossier->titre_dossier }}</div>
                        </td>
                        <td class="px-6 py-4 text-gray-700">
                            {{ $dossier->client->nom }} {{ $dossier->client->prenom }}
                        </td>
                        <td class="px-6 py-4 text-gray-600">
                            {{ $dossier->domaine_droit->label() }}
                        </td>
                        <td class="px-6 py-4">
                            @php
                                $roleDossier = $dossier->pivot->role_dossier;
                            @endphp
                            @if ($roleDossier === \App\Enums\DossierUserRoleEnum::Responsable->value)
                                <span class="rounded-full bg-purple-50 px-2.5 py-1 text-xs font-semibold text-purple-700">
                                    {{ __('Responsable') }}
                                </span>
                            @elseif ($roleDossier === \App\Enums\DossierUserRoleEnum::Collaborateur->value)
                                <span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700">
                                    {{ __('Collaborateur') }}
                                </span>
                            @else
                                <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700">
                                    {{ __('Observateur') }}
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700">
                                {{ $dossier->statut_dossier->label() }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('dossiers.show', $dossier) }}" wire:navigate class="text-xs font-medium text-indigo-600 hover:text-indigo-900">
                                {{ __('Consulter') }} &rarr;
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500">
                            {{ __('Aucun dossier actif ne vous est actuellement assigné.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
