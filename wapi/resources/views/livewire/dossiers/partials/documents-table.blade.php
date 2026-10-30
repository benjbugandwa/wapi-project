<section class="mt-6 space-y-4">
    @can('upload', \App\Models\Document::class)
        <div class="bg-white p-6 shadow-sm sm:rounded-lg">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-lg font-medium text-gray-900">{{ __('Pieces jointes') }}</h2>
                <button type="button" x-data="" x-on:click.prevent="$dispatch('open-modal', 'dossier-document-upload')" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800">{{ __('Ajouter') }}</button>
            </div>
        </div>
    @endcan

    <div class="overflow-hidden border border-gray-200 bg-white shadow-sm sm:rounded-lg">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                <tr>
                    <th class="px-4 py-3">{{ __('Document') }}</th>
                    <th class="px-4 py-3">{{ __('Categorie') }}</th>
                    <th class="px-4 py-3">{{ __('Taille') }}</th>
                    <th class="px-4 py-3 text-right">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($documents as $document)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-900">{{ $document->nom_original }}</div>
                            <div class="text-xs text-gray-500">{{ $document->type_mime }}</div>
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ $document->categorie }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ number_format($document->taille_octets / 1024, 1) }} Ko</td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-3">
                                @can('download', $document)
                                    <a href="{{ route('documents.download', $document) }}" class="text-sm font-medium text-gray-900 hover:text-gray-700">{{ __('Telecharger') }}</a>
                                @endcan
                                @can('delete', $document)
                                    <button type="button" wire:click="deleteDocument('{{ $document->id }}')" wire:confirm="{{ __('Voulez-vous vraiment supprimer ce document ?') }}" wire:loading.attr="disabled" class="text-sm font-medium text-red-700 hover:text-red-900">{{ __('Supprimer') }}</button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-6 text-center text-gray-500">{{ __('Aucun document pour le moment.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
