<?php

use App\DTO\CreateCabinetData;
use App\Models\Cabinet;
use App\Services\CabinetService;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public string $denomination = '';
    public string $adresse_cabinet = '';
    public ?string $description = null;
    public string $nom_repondant = '';
    public string $telephone_repondant = '';
    public ?string $email_repondant = null;

    public function mount(): void
    {
        Gate::authorize('create', Cabinet::class);
    }

    public function save(CabinetService $cabinetService): void
    {
        $validated = $this->validate([
            'denomination' => ['required', 'string', 'max:255'],
            'adresse_cabinet' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'nom_repondant' => ['required', 'string', 'max:150'],
            'telephone_repondant' => ['required', 'string', 'max:50'],
            'email_repondant' => ['nullable', 'email', 'max:255'],
        ]);

        $cabinetService->create(new CreateCabinetData(
            denomination: $validated['denomination'],
            adresseCabinet: $validated['adresse_cabinet'],
            nomRepondant: $validated['nom_repondant'],
            telephoneRepondant: $validated['telephone_repondant'],
            description: $validated['description'] ?? null,
            emailRepondant: $validated['email_repondant'] ?? null,
        ), auth()->user());

        notify()->success(__('Cabinet cree.'), __('Succes'));

        $this->redirectRoute('admin.cabinets.index', navigate: true);
    }
}; ?>

<div class="py-8">
    <x-modal name="cabinet-create-form" :show="true" maxWidth="2xl" :closeable="false" focusable>
        <form wire:submit="save" class="space-y-5 p-6">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">{{ __('Creer un cabinet') }}</h1>
                <p class="mt-1 text-sm text-gray-600">{{ __('Ajoutez un nouveau tenant avec son repondant principal.') }}</p>
            </div>

            <div>
                <x-input-label for="denomination" :value="__('Denomination')" />
                <x-text-input id="denomination" wire:model="denomination" class="mt-1 block w-full" />
                <x-input-error :messages="$errors->get('denomination')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="adresse_cabinet" :value="__('Adresse')" />
                <x-text-input id="adresse_cabinet" wire:model="adresse_cabinet" class="mt-1 block w-full" />
                <x-input-error :messages="$errors->get('adresse_cabinet')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="description" :value="__('Description')" />
                <textarea id="description" wire:model="description" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                <x-input-error :messages="$errors->get('description')" class="mt-2" />
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <x-input-label for="nom_repondant" :value="__('Repondant')" />
                    <x-text-input id="nom_repondant" wire:model="nom_repondant" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('nom_repondant')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="telephone_repondant" :value="__('Telephone')" />
                    <x-text-input id="telephone_repondant" wire:model="telephone_repondant" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('telephone_repondant')" class="mt-2" />
                </div>
            </div>

            <div>
                <x-input-label for="email_repondant" :value="__('Email du repondant')" />
                <x-text-input id="email_repondant" wire:model="email_repondant" type="email" class="mt-1 block w-full" />
                <x-input-error :messages="$errors->get('email_repondant')" class="mt-2" />
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-gray-100 pt-4">
                <x-loading-spinner target="save" />
                <a href="{{ route('admin.cabinets.index') }}" wire:navigate class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700">{{ __('Annuler') }}</a>
                <x-primary-button wire:loading.attr="disabled" wire:target="save">{{ __('Enregistrer') }}</x-primary-button>
            </div>
        </form>
    </x-modal>
</div>
