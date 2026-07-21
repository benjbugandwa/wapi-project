<?php

use App\DTO\SavePartieAdverseData;
use App\DTO\UploadDocumentData;
use App\Enums\ClientTypeEnum;
use App\Enums\DossierUserRoleEnum;
use App\Models\Document;
use App\Models\Dossier;
use App\Models\User;
use App\Services\DocumentService;
use App\Services\DossierAssignmentService;
use App\Services\DossierService;
use App\Services\PartieAdverseService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts.app')] class extends Component
{
    use WithFileUploads;

    public Dossier $dossier;

    public ?string $responsable_id = null;

    /**
     * @var array<int, string>
     */
    public array $collaborateur_ids = [];

    /**
     * @var array<int, string>
     */
    public array $observateur_ids = [];

    public string $type_partie = 'personne_physique';
    public string $nom = '';
    public ?string $prenom = null;
    public ?string $email = null;
    public ?string $telephone = null;
    public ?string $adresse = null;
    public ?string $avocat_nom = null;
    public ?string $avocat_email = null;
    public ?string $notes = null;

    public $document_file = null;
    public string $categorie = 'piece_dossier';
    public bool $est_confidentiel = true;

    public function mount(Dossier $dossier): void
    {
        Gate::authorize('view', $dossier);

        $this->dossier = $dossier;
        $this->loadAssignments();
        $this->loadPartieAdverse();
    }

    public function close(DossierService $dossierService): void
    {
        Gate::authorize('close', $this->dossier);

        $this->dossier = $dossierService->close($this->dossier, auth()->user());

        notify()->success(__('Dossier cloture.'), __('Succes'));
    }

    public function archive(DossierService $dossierService): void
    {
        Gate::authorize('archive', $this->dossier);

        $this->dossier = $dossierService->archive($this->dossier, auth()->user());

        notify()->success(__('Dossier archive.'), __('Succes'));
    }

    public function saveAssignments(DossierAssignmentService $assignmentService): void
    {
        Gate::authorize('assign', $this->dossier);

        $validated = $this->validate([
            'responsable_id' => [
                'required',
                'uuid',
                Rule::exists('users', 'id')->where('cabinet_id', $this->dossier->cabinet_id),
            ],
            'collaborateur_ids' => ['array'],
            'collaborateur_ids.*' => [
                'uuid',
                Rule::exists('users', 'id')->where('cabinet_id', $this->dossier->cabinet_id),
            ],
            'observateur_ids' => ['array'],
            'observateur_ids.*' => [
                'uuid',
                Rule::exists('users', 'id')->where('cabinet_id', $this->dossier->cabinet_id),
            ],
        ]);

        $assignments = [[
            'user_id' => $validated['responsable_id'],
            'role_dossier' => DossierUserRoleEnum::Responsable->value,
        ]];

        foreach (array_unique($validated['collaborateur_ids'] ?? []) as $userId) {
            if ($userId !== $validated['responsable_id']) {
                $assignments[] = [
                    'user_id' => $userId,
                    'role_dossier' => DossierUserRoleEnum::Collaborateur->value,
                ];
            }
        }

        foreach (array_unique($validated['observateur_ids'] ?? []) as $userId) {
            if ($userId !== $validated['responsable_id']) {
                $assignments[] = [
                    'user_id' => $userId,
                    'role_dossier' => DossierUserRoleEnum::Observateur->value,
                ];
            }
        }

        $assignmentService->sync($this->dossier, $assignments, auth()->user());
        $this->loadAssignments();

        notify()->success(__('Affectations mises a jour.'), __('Succes'));
    }

    public function savePartieAdverse(PartieAdverseService $partieAdverseService): void
    {
        Gate::authorize('update', $this->dossier);

        $validated = $this->validate([
            'type_partie' => ['required', Rule::in(ClientTypeEnum::values())],
            'nom' => ['required', 'string', 'max:150'],
            'prenom' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'telephone' => ['nullable', 'string', 'max:50'],
            'adresse' => ['nullable', 'string', 'max:255'],
            'avocat_nom' => ['nullable', 'string', 'max:150'],
            'avocat_email' => ['nullable', 'email', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $partieAdverseService->save($this->dossier, new SavePartieAdverseData(
            typePartie: ClientTypeEnum::from($validated['type_partie']),
            nom: $validated['nom'],
            prenom: $validated['prenom'] ?? null,
            email: $validated['email'] ?? null,
            telephone: $validated['telephone'] ?? null,
            adresse: $validated['adresse'] ?? null,
            avocatNom: $validated['avocat_nom'] ?? null,
            avocatEmail: $validated['avocat_email'] ?? null,
            notes: $validated['notes'] ?? null,
        ), auth()->user());

        notify()->success(__('Partie adverse enregistree.'), __('Succes'));
    }

    public function uploadDocument(DocumentService $documentService): void
    {
        Gate::authorize('view', $this->dossier);
        Gate::authorize('upload', Document::class);

        $validated = $this->validate([
            'document_file' => ['required', 'file', 'max:51200', 'mimes:doc,docx,xls,xlsx,pdf,png,jpg,jpeg,mp3,wav,mp4'],
            'categorie' => ['required', 'string', 'max:100'],
            'est_confidentiel' => ['boolean'],
        ]);

        $documentService->upload(new UploadDocumentData(
            owner: $this->dossier,
            file: $validated['document_file'],
            categorie: $validated['categorie'],
            estConfidentiel: $validated['est_confidentiel'],
        ), auth()->user());

        $this->reset('document_file');
        $this->categorie = 'piece_dossier';
        $this->est_confidentiel = true;

        notify()->success(__('Document ajoute.'), __('Succes'));
    }

    public function deleteDocument(string $documentId, DocumentService $documentService): void
    {
        $document = Document::query()->where('dossier_id', $this->dossier->id)->findOrFail($documentId);

        Gate::authorize('delete', $document);

        $documentService->delete($document, auth()->user());

        notify()->success(__('Document supprime.'), __('Succes'));
    }

    public function with(): array
    {
        $this->dossier->load(['client', 'assignedUsers', 'partiesAdverses', 'documents.uploadedBy']);

        return [
            'availableUsers' => User::query()
                ->where('cabinet_id', $this->dossier->cabinet_id)
                ->orderBy('nom')
                ->get(),
            'roleDossierOptions' => DossierUserRoleEnum::cases(),
            'typesPartie' => ClientTypeEnum::cases(),
            'documents' => $this->dossier->documents()
                ->with(['uploadedBy'])
                ->latest()
                ->get(),
            'partieAdverse' => $this->dossier->partiesAdverses()->latest()->first(),
        ];
    }

    private function loadAssignments(): void
    {
        $this->dossier->load('assignedUsers');

        $this->responsable_id = $this->dossier->assignedUsers
            ->first(fn (User $user): bool => $user->pivot->role_dossier === DossierUserRoleEnum::Responsable->value)
            ?->id;

        $this->collaborateur_ids = $this->dossier->assignedUsers
            ->filter(fn (User $user): bool => $user->pivot->role_dossier === DossierUserRoleEnum::Collaborateur->value)
            ->pluck('id')
            ->values()
            ->all();

        $this->observateur_ids = $this->dossier->assignedUsers
            ->filter(fn (User $user): bool => $user->pivot->role_dossier === DossierUserRoleEnum::Observateur->value)
            ->pluck('id')
            ->values()
            ->all();
    }

    private function loadPartieAdverse(): void
    {
        $partieAdverse = $this->dossier->partiesAdverses()->latest()->first();

        if ($partieAdverse === null) {
            return;
        }

        $this->type_partie = $partieAdverse->type_partie->value;
        $this->nom = $partieAdverse->nom;
        $this->prenom = $partieAdverse->prenom;
        $this->email = $partieAdverse->email;
        $this->telephone = $partieAdverse->telephone;
        $this->adresse = $partieAdverse->adresse;
        $this->avocat_nom = $partieAdverse->avocat_nom;
        $this->avocat_email = $partieAdverse->avocat_email;
        $this->notes = $partieAdverse->notes;
    }
}; ?>

<div class="py-8">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">{{ $dossier->numero_reference }} - {{ $dossier->titre_dossier }}</h1>
                <p class="mt-1 text-sm text-gray-600">{{ __('Client') }}: {{ $dossier->client->nom }} @if ($dossier->client->prenom) {{ $dossier->client->prenom }} @endif</p>
            </div>

            <div class="flex flex-wrap gap-3">
                <a href="{{ route('dossiers.index') }}" wire:navigate class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700">{{ __('Retour') }}</a>
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

        <div class="grid gap-6 lg:grid-cols-[0.95fr_1.05fr]">
            <section class="bg-white p-6 shadow-sm sm:rounded-lg">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-lg font-medium text-gray-900">{{ __('Informations dossier') }}</h2>
                    <span class="rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700">{{ $dossier->statut_dossier->label() }}</span>
                </div>

                <dl class="mt-5 grid gap-4 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="font-medium text-gray-500">{{ __('Domaine') }}</dt>
                        <dd class="mt-1 text-gray-900">{{ $dossier->domaine_droit->label() }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">{{ __('Age') }}</dt>
                        <dd class="mt-1 text-gray-900">{{ $dossier->ageInDays() }} {{ __('jours') }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">{{ __('Date d ouverture') }}</dt>
                        <dd class="mt-1 text-gray-900">{{ $dossier->date_ouverture->format('Y-m-d') }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">{{ __('Date de cloture') }}</dt>
                        <dd class="mt-1 text-gray-900">{{ $dossier->date_cloture?->format('Y-m-d') ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">{{ __('Juridiction') }}</dt>
                        <dd class="mt-1 text-gray-900">{{ $dossier->juridiction ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">{{ __('Facturation') }}</dt>
                        <dd class="mt-1 text-gray-900">{{ $dossier->type_facturation->label() }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="font-medium text-gray-500">{{ __('Description') }}</dt>
                        <dd class="mt-1 whitespace-pre-line text-gray-900">{{ $dossier->description ?? '-' }}</dd>
                    </div>
                </dl>
            </section>

            <section class="space-y-6">
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
                                <span class="text-gray-600">{{ DossierUserRoleEnum::from($assignedUser->pivot->role_dossier)->label() }}</span>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500">{{ __('Aucun collaborateur affecte.') }}</p>
                        @endforelse
                    </div>
                </div>

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
            </section>
        </div>

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

        <x-modal name="dossier-assignments-form" maxWidth="2xl" focusable>
            <form wire:submit="saveAssignments" class="space-y-5 p-6">
                <h2 class="text-lg font-medium text-gray-900">{{ __('Affecter les collaborateurs') }}</h2>

                <div>
                    <x-input-label for="responsable_id" :value="__('Avocat responsable')" />
                    <select id="responsable_id" wire:model="responsable_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">{{ __('Selectionner') }}</option>
                        @foreach ($availableUsers as $availableUser)
                            <option value="{{ $availableUser->id }}">{{ $availableUser->nom }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('responsable_id')" class="mt-2" />
                    <x-input-error :messages="$errors->get('assignments')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="collaborateur_ids" :value="__('Collaborateurs')" />
                    <select id="collaborateur_ids" wire:model="collaborateur_ids" multiple class="mt-1 block min-h-32 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @foreach ($availableUsers as $availableUser)
                            <option value="{{ $availableUser->id }}">{{ $availableUser->nom }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('collaborateur_ids')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="observateur_ids" :value="__('Observateurs')" />
                    <select id="observateur_ids" wire:model="observateur_ids" multiple class="mt-1 block min-h-32 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @foreach ($availableUsers as $availableUser)
                            <option value="{{ $availableUser->id }}">{{ $availableUser->nom }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('observateur_ids')" class="mt-2" />
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-gray-100 pt-4">
                    <x-loading-spinner target="saveAssignments" />
                    <x-secondary-button type="button" x-on:click="$dispatch('close')">{{ __('Annuler') }}</x-secondary-button>
                    <x-primary-button wire:loading.attr="disabled" wire:target="saveAssignments">{{ __('Enregistrer') }}</x-primary-button>
                </div>
            </form>
        </x-modal>

        <x-modal name="partie-adverse-form" maxWidth="2xl" focusable>
            <form wire:submit="savePartieAdverse" class="space-y-5 p-6">
                <h2 class="text-lg font-medium text-gray-900">{{ __('Partie adverse') }}</h2>

                <div>
                    <x-input-label for="type_partie" :value="__('Type')" />
                    <select id="type_partie" wire:model.live="type_partie" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @foreach ($typesPartie as $type)
                            <option value="{{ $type->value }}">{{ $type->label() }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('type_partie')" class="mt-2" />
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <x-input-label for="nom" :value="$type_partie === 'personne_morale' ? __('Raison sociale') : __('Nom')" />
                        <x-text-input id="nom" wire:model="nom" class="mt-1 block w-full" />
                        <x-input-error :messages="$errors->get('nom')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="prenom" :value="__('Prenom')" />
                        <x-text-input id="prenom" wire:model="prenom" class="mt-1 block w-full" :disabled="$type_partie === 'personne_morale'" />
                        <x-input-error :messages="$errors->get('prenom')" class="mt-2" />
                    </div>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <x-input-label for="email" :value="__('Email')" />
                        <x-text-input id="email" wire:model="email" type="email" class="mt-1 block w-full" />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="telephone" :value="__('Telephone')" />
                        <x-text-input id="telephone" wire:model="telephone" class="mt-1 block w-full" />
                        <x-input-error :messages="$errors->get('telephone')" class="mt-2" />
                    </div>
                </div>

                <div>
                    <x-input-label for="adresse" :value="__('Adresse')" />
                    <x-text-input id="adresse" wire:model="adresse" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('adresse')" class="mt-2" />
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <x-input-label for="avocat_nom" :value="__('Avocat adverse')" />
                        <x-text-input id="avocat_nom" wire:model="avocat_nom" class="mt-1 block w-full" />
                        <x-input-error :messages="$errors->get('avocat_nom')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="avocat_email" :value="__('Email avocat')" />
                        <x-text-input id="avocat_email" wire:model="avocat_email" type="email" class="mt-1 block w-full" />
                        <x-input-error :messages="$errors->get('avocat_email')" class="mt-2" />
                    </div>
                </div>

                <div>
                    <x-input-label for="notes" :value="__('Notes')" />
                    <textarea id="notes" wire:model="notes" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                    <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-gray-100 pt-4">
                    <x-loading-spinner target="savePartieAdverse" />
                    <x-secondary-button type="button" x-on:click="$dispatch('close')">{{ __('Annuler') }}</x-secondary-button>
                    <x-primary-button wire:loading.attr="disabled" wire:target="savePartieAdverse">{{ __('Enregistrer') }}</x-primary-button>
                </div>
            </form>
        </x-modal>

        <x-modal name="dossier-document-upload" maxWidth="2xl" focusable>
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
    </div>
</div>
