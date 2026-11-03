<div class="bg-white p-6 shadow-sm sm:rounded-lg">
    <div class="flex items-center justify-between gap-3">
        <h2 class="text-lg font-medium text-gray-900">{{ __('Partie adverse') }}</h2>
        @can('update', $dossier)
            <button type="button" x-data="" x-on:click.prevent="$dispatch('open-modal', 'partie-adverse-form')" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800">{{ $partieAdverse ? __('Modifier') : __('Ajouter') }}</button>
        @endcan
    </div>

    @if ($partieAdverse)
        <dl class="mt-4 grid gap-4 text-sm sm:grid-cols-2">
            <div>
                <dt class="font-medium text-gray-500">{{ __('Nom') }}</dt>
                <dd class="mt-1 text-gray-900">{{ $partieAdverse->nom }} @if ($partieAdverse->prenom) {{ $partieAdverse->prenom }} @endif</dd>
            </div>
            <div>
                <dt class="font-medium text-gray-500">{{ __('Contact') }}</dt>
                <dd class="mt-1 text-gray-900">{{ $partieAdverse->email ?? '-' }} / {{ $partieAdverse->telephone ?? '-' }}</dd>
            </div>
            <div>
                <dt class="font-medium text-gray-500">{{ __('Avocat') }}</dt>
                <dd class="mt-1 text-gray-900">{{ $partieAdverse->avocat_nom ?? '-' }}</dd>
            </div>
            <div>
                <dt class="font-medium text-gray-500">{{ __('Adresse') }}</dt>
                <dd class="mt-1 text-gray-900">{{ $partieAdverse->adresse ?? '-' }}</dd>
            </div>
        </dl>
    @else
        <p class="mt-4 text-sm text-gray-500">{{ __('Aucune partie adverse enregistree.') }}</p>
    @endif
</div>
