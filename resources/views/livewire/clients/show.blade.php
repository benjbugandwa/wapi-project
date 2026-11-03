<?php

use App\DTO\UploadClientDocumentData;
use App\Models\Client;
use App\Models\Document;
use App\Services\ClientDocumentService;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts.app')] class extends Component
{
    use WithFileUploads;

    public Client $client;
    public $document_file = null;
    public string $categorie = 'piece_identite';
    public bool $est_confidentiel = true;

    public function mount(Client $client): void
    {
        Gate::authorize('view', $client);

        $this->client = $client;
    }

    public function uploadDocument(ClientDocumentService $clientDocumentService): void
    {
        Gate::authorize('view', $this->client);
        Gate::authorize('upload', Document::class);

        $validated = $this->validate([
            'document_file' => ['required', 'file', 'max:51200', 'mimes:doc,docx,xls,xlsx,pdf,png,jpg,jpeg,mp3,wav,mp4'],
            'categorie' => ['required', 'string', 'max:100'],
            'est_confidentiel' => ['boolean'],
        ]);

        $clientDocumentService->upload(new UploadClientDocumentData(
            client: $this->client,
            file: $validated['document_file'],
            categorie: $validated['categorie'],
            estConfidentiel: $validated['est_confidentiel'],
        ), auth()->user());

        $this->reset('document_file');
        $this->categorie = 'piece_identite';
        $this->est_confidentiel = true;

        notify()->success(__('Document ajoute.'), __('Succes'));
    }

    public function deleteDocument(string $documentId, ClientDocumentService $clientDocumentService): void
    {
        $document = Document::query()->where('client_id', $this->client->id)->findOrFail($documentId);

        Gate::authorize('delete', $document);

        $clientDocumentService->delete($document, auth()->user());

        notify()->success(__('Document supprime.'), __('Succes'));
    }

    public function with(): array
    {
        return [
            'documents' => $this->client->documents()
                ->with(['uploadedBy'])
                ->latest()
                ->get()
                ->filter(fn (Document $doc): bool => auth()->user()?->can('view', $doc) ?? false),
        ];
    }
}; ?>

<div class="py-8">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">{{ $client->nom }} @if ($client->prenom) {{ $client->prenom }} @endif</h1>
                <p class="mt-1 text-sm text-gray-600">{{ $client->type_client->label() }}</p>
            </div>

            <div class="flex gap-3">
                <a href="{{ route('clients.index') }}" wire:navigate class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700">{{ __('Retour') }}</a>
                @can('update', $client)
                    <a href="{{ route('clients.edit', $client) }}" wire:navigate class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800">{{ __('Modifier') }}</a>
                @endcan
            </div>
        </div>

        <x-loading-spinner target="uploadDocument,deleteDocument" class="mb-4" />

        <div class="grid gap-6 lg:grid-cols-[0.9fr_1.1fr]">
            <section class="bg-white p-6 shadow-sm sm:rounded-lg">
                <h2 class="text-lg font-medium text-gray-900">{{ __('Informations client') }}</h2>

                <dl class="mt-5 grid gap-4 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="font-medium text-gray-500">{{ __('Email') }}</dt>
                        <dd class="mt-1 text-gray-900">{{ $client->email ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">{{ __('Telephone') }}</dt>
                        <dd class="mt-1 text-gray-900">{{ $client->telephone_principal ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">{{ __('Adresse') }}</dt>
                        <dd class="mt-1 text-gray-900">{{ $client->adresse_rue ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">{{ __('Ville') }}</dt>
                        <dd class="mt-1 text-gray-900">{{ $client->ville ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">{{ __('Numero d identification') }}</dt>
                        <dd class="mt-1 text-gray-900">{{ $client->numero_identification ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">{{ __('Representant legal') }}</dt>
                        <dd class="mt-1 text-gray-900">{{ $client->representant_legal ?? '-' }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="font-medium text-gray-500">{{ __('Notes internes') }}</dt>
                        <dd class="mt-1 whitespace-pre-line text-gray-900">{{ $client->notes_internes ?? '-' }}</dd>
                    </div>
                </dl>
            </section>

            <section class="space-y-6">
                @can('upload', \App\Models\Document::class)
                    <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                        <div class="flex items-center justify-between gap-3">
                            <h2 class="text-lg font-medium text-gray-900">{{ __('Pieces jointes') }}</h2>
                            <button
                                type="button"
                                x-data=""
                                x-on:click.prevent="$dispatch('open-modal', 'client-document-upload')"
                                class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800"
                            >{{ __('Ajouter') }}</button>
                        </div>
                    </div>

                    <x-modal name="client-document-upload" maxWidth="2xl" focusable>
                        <form wire:submit="uploadDocument" class="space-y-4 p-6">
                            <h2 class="text-lg font-medium text-gray-900">{{ __('Ajouter une piece jointe') }}</h2>

                            <div class="grid gap-4 md:grid-cols-3">
                            <div>
                                <x-input-label for="document_file" :value="__('Fichier')" />
                                <input id="document_file" wire:model="document_file" type="file" class="mt-1 block w-full text-sm text-gray-700" />
                                <x-input-error :messages="$errors->get('document_file')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="categorie" :value="__('Categorie')" />
                                <x-text-input id="categorie" wire:model="categorie" class="mt-1 block w-full" />
                                <x-input-error :messages="$errors->get('categorie')" class="mt-2" />
                            </div>

                            <div class="flex items-end">
                                <label class="inline-flex items-center">
                                    <input wire:model="est_confidentiel" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                    <span class="ms-2 text-sm text-gray-600">{{ __('Confidentiel') }}</span>
                                </label>
                            </div>
                            </div>

                            <div class="flex items-center justify-end gap-3 border-t border-gray-100 pt-4">
                                <x-loading-spinner target="uploadDocument" />
                                <x-secondary-button type="button" x-on:click="$dispatch('close')">{{ __('Annuler') }}</x-secondary-button>
                                <x-primary-button wire:loading.attr="disabled" wire:target="uploadDocument">{{ __('Ajouter') }}</x-primary-button>
                            </div>
                        </form>
                    </x-modal>
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
                                                <button
                                                    type="button"
                                                    wire:click="deleteDocument('{{ $document->id }}')"
                                                    wire:confirm="{{ __('Voulez-vous vraiment supprimer ce document ?') }}"
                                                    wire:loading.attr="disabled"
                                                    class="text-sm font-medium text-red-700 hover:text-red-900"
                                                >{{ __('Supprimer') }}</button>
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
        </div>
    </div>
</div>
