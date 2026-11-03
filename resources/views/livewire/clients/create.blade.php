<?php

use App\DTO\CreateClientData;
use App\Enums\ClientTypeEnum;
use App\Models\Client;
use App\Services\ClientService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public string $type_client = 'personne_physique';
    public string $nom = '';
    public ?string $prenom = null;
    public ?string $email = null;
    public ?string $telephone_principal = null;
    public ?string $adresse_rue = null;
    public ?string $ville = null;
    public ?string $numero_identification = null;
    public ?string $representant_legal = null;
    public ?string $notes_internes = null;

    public function mount(): void
    {
        Gate::authorize('create', Client::class);
    }

    public function save(ClientService $clientService): void
    {
        $validated = $this->validate([
            'type_client' => ['required', Rule::in(ClientTypeEnum::values())],
            'nom' => ['required', 'string', 'max:150'],
            'prenom' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'telephone_principal' => ['nullable', 'string', 'max:50'],
            'adresse_rue' => ['nullable', 'string', 'max:255'],
            'ville' => ['nullable', 'string', 'max:100'],
            'numero_identification' => ['nullable', 'string', 'max:100'],
            'representant_legal' => ['nullable', 'string', 'max:150'],
            'notes_internes' => ['nullable', 'string', 'max:5000'],
        ]);

        $client = $clientService->create(new CreateClientData(
            typeClient: ClientTypeEnum::from($validated['type_client']),
            nom: $validated['nom'],
            prenom: $validated['prenom'] ?? null,
            email: $validated['email'] ?? null,
            telephonePrincipal: $validated['telephone_principal'] ?? null,
            adresseRue: $validated['adresse_rue'] ?? null,
            ville: $validated['ville'] ?? null,
            numeroIdentification: $validated['numero_identification'] ?? null,
            representantLegal: $validated['representant_legal'] ?? null,
            notesInternes: $validated['notes_internes'] ?? null,
        ), auth()->user());

        notify()->success(__('Client cree.'), __('Succes'));

        $this->redirectRoute('clients.show', $client, navigate: true);
    }

    public function with(): array
    {
        return ['types' => ClientTypeEnum::cases()];
    }
}; ?>

<div class="py-8">
    <x-modal name="client-create-form" :show="true" maxWidth="2xl" :closeable="false" focusable>
        <form wire:submit="save" class="space-y-5 p-6">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">{{ __('Nouveau client') }}</h1>
                <p class="mt-1 text-sm text-gray-600">{{ __('Renseignez les informations du client a creer.') }}</p>
            </div>

            <div>
                <x-input-label for="type_client" :value="__('Type de client')" />
                <select id="type_client" wire:model.live="type_client" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @foreach ($types as $type)
                        <option value="{{ $type->value }}">{{ $type->label() }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('type_client')" class="mt-2" />
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <x-input-label for="nom" :value="$type_client === 'personne_morale' ? __('Raison sociale') : __('Nom')" />
                    <x-text-input id="nom" wire:model="nom" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('nom')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="prenom" :value="__('Prenom')" />
                    <x-text-input id="prenom" wire:model="prenom" class="mt-1 block w-full" :disabled="$type_client === 'personne_morale'" />
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
                    <x-input-label for="telephone_principal" :value="__('Telephone')" />
                    <x-text-input id="telephone_principal" wire:model="telephone_principal" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('telephone_principal')" class="mt-2" />
                </div>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <x-input-label for="adresse_rue" :value="__('Adresse')" />
                    <x-text-input id="adresse_rue" wire:model="adresse_rue" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('adresse_rue')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="ville" :value="__('Ville')" />
                    <x-text-input id="ville" wire:model="ville" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('ville')" class="mt-2" />
                </div>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <x-input-label for="numero_identification" :value="__('Numero d identification')" />
                    <x-text-input id="numero_identification" wire:model="numero_identification" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('numero_identification')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="representant_legal" :value="__('Representant legal')" />
                    <x-text-input id="representant_legal" wire:model="representant_legal" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('representant_legal')" class="mt-2" />
                </div>
            </div>

            <div>
                <x-input-label for="notes_internes" :value="__('Notes internes')" />
                <textarea id="notes_internes" wire:model="notes_internes" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                <x-input-error :messages="$errors->get('notes_internes')" class="mt-2" />
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-gray-100 pt-4">
                <x-loading-spinner target="save" />
                <a href="{{ route('clients.index') }}" wire:navigate class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700">{{ __('Annuler') }}</a>
                <x-primary-button wire:loading.attr="disabled" wire:target="save">{{ __('Enregistrer') }}</x-primary-button>
            </div>
        </form>
    </x-modal>
</div>
