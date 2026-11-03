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
                ->get()
                ->filter(fn (Document $doc): bool => auth()->user()?->can('view', $doc) ?? false),
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
        {{-- En-tête et actions --}}
        @include('livewire.dossiers.partials.header')

        {{-- Métadonnées et parties prenantes --}}
        <div class="grid gap-6 lg:grid-cols-[0.95fr_1.05fr]">
            @include('livewire.dossiers.partials.info-card')

            <section class="space-y-6">
                @include('livewire.dossiers.partials.collaborators-card')
                @include('livewire.dossiers.partials.partie-adverse-card')
            </section>
        </div>

        {{-- Table des pièces jointes --}}
        @include('livewire.dossiers.partials.documents-table')

        {{-- Modales d'action avec gestion Alpine.js --}}
        <x-dossiers.assignments-modal :available-users="$availableUsers" />
        <x-dossiers.partie-adverse-modal :types-partie="$typesPartie" :type-partie="$type_partie" />
        <x-dossiers.document-upload-modal />
    </div>
</div>
