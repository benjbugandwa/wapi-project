<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-xl font-semibold text-gray-900">{{ $dossier->numero_reference }} - {{ $dossier->titre_dossier }}</h1>
        <p class="mt-1 text-sm text-gray-600">{{ __('Client') }}: {{ $dossier->client->nom }} @if ($dossier->client->prenom) {{ $dossier->client->prenom }} @endif</p>
    </div>

    <div class="flex flex-wrap gap-3">
        <a href="{{ route('dossiers.index') }}" wire:navigate class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">{{ __('Retour') }}</a>
        @can('update', $dossier)
            <a href="{{ route('dossiers.edit', $dossier) }}" wire:navigate class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800">{{ __('Modifier') }}</a>
        @endcan
        @can('close', $dossier)
            <button type="button" wire:click="close" wire:confirm="{{ __('Voulez-vous vraiment cloturer ce dossier ?') }}" wire:loading.attr="disabled" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800">{{ __('Cloturer') }}</button>
        @endcan
        @can('archive', $dossier)
            <button type="button" wire:click="archive" wire:confirm="{{ __('Voulez-vous vraiment archiver ce dossier ?') }}" wire:loading.attr="disabled" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800">{{ __('Archiver') }}</button>
        @endcan
    </div>
</div>

<x-loading-spinner target="close,archive,saveAssignments,savePartieAdverse,uploadDocument,deleteDocument" class="mb-4" />
