@props([
    'typesPartie' => [],
    'typePartie' => 'personne_physique',
])

<x-modal name="partie-adverse-form" maxWidth="2xl" focusable>
    <div x-data="{
        typePartie: $wire.entangle('type_partie').live,
        get isMoral() {
            return this.typePartie === 'personne_morale';
        }
    }">
        <form wire:submit="savePartieAdverse" class="space-y-5 p-6">
            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                <h2 class="text-lg font-medium text-gray-900">{{ __('Partie adverse') }}</h2>
                <button type="button" x-on:click="$dispatch('close')" class="text-gray-400 hover:text-gray-500">
                    <span class="sr-only">{{ __('Fermer') }}</span>
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div>
                <x-input-label for="type_partie" :value="__('Type de partie')" />
                <select id="type_partie" wire:model.live="type_partie" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @foreach ($typesPartie as $type)
                        <option value="{{ $type->value }}">{{ $type->label() }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('type_partie')" class="mt-2" />
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="nom" class="block font-medium text-sm text-gray-700">
                        <span x-text="isMoral ? '{{ __('Raison sociale') }}' : '{{ __('Nom') }}'">
                            {{ $typePartie === 'personne_morale' ? __('Raison sociale') : __('Nom') }}
                        </span>
                    </label>
                    <x-text-input id="nom" wire:model="nom" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('nom')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="prenom" :value="__('Prenom')" />
                    <x-text-input id="prenom" wire:model="prenom" class="mt-1 block w-full" x-bind:disabled="isMoral" :disabled="$typePartie === 'personne_morale'" />
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
                <textarea id="notes" wire:model="notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                <x-input-error :messages="$errors->get('notes')" class="mt-2" />
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-gray-100 pt-4">
                <x-loading-spinner target="savePartieAdverse" />
                <x-secondary-button type="button" x-on:click="$dispatch('close')">{{ __('Annuler') }}</x-secondary-button>
                <x-primary-button wire:loading.attr="disabled" wire:target="savePartieAdverse">{{ __('Enregistrer') }}</x-primary-button>
            </div>
        </form>
    </div>
</x-modal>
