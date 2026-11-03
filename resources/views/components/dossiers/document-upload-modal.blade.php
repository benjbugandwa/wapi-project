@props([])

<x-modal name="dossier-document-upload" maxWidth="2xl" focusable>
    <div x-data="{
        isDropping: false,
        fileName: '',
        fileSize: '',
        handleDrop(e) {
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                const input = this.$refs.fileInput;
                input.files = files;
                input.dispatchEvent(new Event('change', { bubbles: true }));
                this.updateFileInfo(files[0]);
            }
        },
        updateFileInfo(file) {
            if (file) {
                this.fileName = file.name;
                const sizeMb = (file.size / (1024 * 1024)).toFixed(2);
                this.fileSize = sizeMb > 1 ? sizeMb + ' Mo' : Math.round(file.size / 1024) + ' Ko';
            } else {
                this.fileName = '';
                this.fileSize = '';
            }
        }
    }">
        <form wire:submit="uploadDocument" class="space-y-5 p-6">
            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                <h2 class="text-lg font-medium text-gray-900">{{ __('Ajouter une piece jointe') }}</h2>
                <button type="button" x-on:click="$dispatch('close')" class="text-gray-400 hover:text-gray-500">
                    <span class="sr-only">{{ __('Fermer') }}</span>
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Drag & Drop Zone -->
            <div
                x-on:dragover.prevent="isDropping = true"
                x-on:dragleave.prevent="isDropping = false"
                x-on:drop.prevent="isDropping = false; handleDrop($event)"
                :class="isDropping ? 'border-indigo-500 bg-indigo-50/50' : 'border-gray-300 hover:border-gray-400'"
                class="relative flex flex-col items-center justify-center rounded-lg border-2 border-dashed p-6 text-center transition"
            >
                <svg class="mx-auto h-10 w-10 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                    <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>

                <div class="mt-2 flex text-sm text-gray-600">
                    <label for="document_file" class="relative cursor-pointer rounded-md font-medium text-indigo-600 focus-within:outline-none focus-within:ring-2 focus-within:ring-indigo-500 focus-within:ring-offset-2 hover:text-indigo-500">
                        <span>{{ __('Choisir un fichier') }}</span>
                        <input
                            id="document_file"
                            x-ref="fileInput"
                            wire:model="document_file"
                            type="file"
                            class="sr-only"
                            x-on:change="updateFileInfo($event.target.files[0])"
                        />
                    </label>
                    <p class="pl-1">{{ __('ou glisser-deposer ici') }}</p>
                </div>
                <p class="text-xs text-gray-500 mt-1">{{ __('DOC, DOCX, XLS, XLSX, PDF, PNG, JPG, MP3, WAV, MP4 jusqu a 50 Mo') }}</p>

                <!-- File preview info -->
                <template x-if="fileName">
                    <div class="mt-3 inline-flex items-center gap-2 rounded-full bg-indigo-50 px-3 py-1 text-xs font-medium text-indigo-700">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span x-text="fileName"></span>
                        <span class="text-gray-400" x-text="'(' + fileSize + ')'"></span>
                    </div>
                </template>
            </div>
            <x-input-error :messages="$errors->get('document_file')" class="mt-2" />

            <!-- Additional options -->
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="categorie" :value="__('Categorie')" />
                    <x-text-input id="categorie" wire:model="categorie" class="mt-1 block w-full" placeholder="Ex: piece_dossier, acte, conclusion..." />
                    <x-input-error :messages="$errors->get('categorie')" class="mt-2" />
                </div>

                <div class="flex items-center pt-6">
                    <label class="inline-flex items-center cursor-pointer">
                        <input wire:model="est_confidentiel" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                        <span class="ms-2 text-sm text-gray-700">{{ __('Document confidentiel (acces restreint)') }}</span>
                    </label>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-gray-100 pt-4">
                <x-loading-spinner target="uploadDocument" />
                <x-secondary-button type="button" x-on:click="$dispatch('close')">{{ __('Annuler') }}</x-secondary-button>
                <x-primary-button wire:loading.attr="disabled" wire:target="uploadDocument">{{ __('Televerser') }}</x-primary-button>
            </div>
        </form>
    </div>
</x-modal>
