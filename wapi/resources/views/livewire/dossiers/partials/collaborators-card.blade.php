<div class="bg-white p-6 shadow-sm sm:rounded-lg">
    <div class="flex items-center justify-between gap-3">
        <h2 class="text-lg font-medium text-gray-900">{{ __('Collaborateurs') }}</h2>
        @can('assign', $dossier)
            <button type="button" x-data="" x-on:click.prevent="$dispatch('open-modal', 'dossier-assignments-form')" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800">{{ __('Affecter') }}</button>
        @endcan
    </div>

    <div class="mt-4 space-y-2 text-sm">
        @forelse ($dossier->assignedUsers as $assignedUser)
            <div class="flex items-center justify-between border-b border-gray-100 py-2">
                <span class="font-medium text-gray-900">{{ $assignedUser->nom }}</span>
                <span class="text-gray-600">{{ \App\Enums\DossierUserRoleEnum::from($assignedUser->pivot->role_dossier)->label() }}</span>
            </div>
        @empty
            <p class="text-sm text-gray-500">{{ __('Aucun collaborateur affecte.') }}</p>
        @endforelse
    </div>
</div>
