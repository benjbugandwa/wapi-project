@props([
    'availableUsers' => [],
])

<x-modal name="dossier-assignments-form" maxWidth="2xl" focusable>
    <div x-data="{
        submitting: false,
    }">
        <form wire:submit="saveAssignments" x-on:submit="submitting = true" class="space-y-5 p-6">
            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                <h2 class="text-lg font-medium text-gray-900">{{ __('Affecter les collaborateurs') }}</h2>
                <button type="button" x-on:click="$dispatch('close')" class="text-gray-400 hover:text-gray-500">
                    <span class="sr-only">{{ __('Fermer') }}</span>
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div>
                <x-input-label for="responsable_id" :value="__('Avocat responsable')" />
                <select id="responsable_id" wire:model="responsable_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">{{ __('Selectionner un avocat responsable') }}</option>
                    @foreach ($availableUsers as $availableUser)
                        <option value="{{ $availableUser->id }}">{{ $availableUser->nom }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('responsable_id')" class="mt-2" />
                <x-input-error :messages="$errors->get('assignments')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="collaborateur_ids" :value="__('Collaborateurs')" />
                <p class="text-xs text-gray-500 mb-1">{{ __('Maintenez Ctrl (ou Cmd) pour selectionner plusieurs collaborateurs.') }}</p>
                <select id="collaborateur_ids" wire:model="collaborateur_ids" multiple class="mt-1 block min-h-32 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @foreach ($availableUsers as $availableUser)
                        <option value="{{ $availableUser->id }}">{{ $availableUser->nom }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('collaborateur_ids')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="observateur_ids" :value="__('Observateurs')" />
                <p class="text-xs text-gray-500 mb-1">{{ __('Membres en acces consultation uniquement.') }}</p>
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
    </div>
</x-modal>
