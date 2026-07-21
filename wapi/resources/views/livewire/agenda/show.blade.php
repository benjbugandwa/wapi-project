<?php

use App\DTO\SaveRappelsData;
use App\Enums\NotificationChannelEnum;
use App\Models\Evenement;
use App\Models\Rappel;
use App\Services\RappelService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public Evenement $evenement;

    public int $delai_minutes = 60;

    /**
     * @var array<int, string>
     */
    public array $channels = ['email'];

    public function mount(Evenement $evenement): void
    {
        Gate::authorize('view', $evenement);

        $this->evenement = $evenement;
        $this->loadExistingRappels();
    }

    public function saveRappels(RappelService $rappelService): void
    {
        Gate::authorize('update', Rappel::class);

        $validated = $this->validate([
            'delai_minutes' => ['required', 'integer', 'min:1', 'max:43200'],
            'channels' => ['required', 'array', 'min:1'],
            'channels.*' => ['required', Rule::in(NotificationChannelEnum::values())],
        ]);

        $rappelService->saveForEvenement($this->evenement, new SaveRappelsData(
            delaiMinutes: (int) $validated['delai_minutes'],
            channels: array_values(array_unique($validated['channels'])),
        ));

        $this->loadExistingRappels();
        $this->dispatch('close-modal', 'rappels-form');

        notify()->success(__('Rappels mis a jour.'), __('Succes'));
    }

    public function with(): array
    {
        $this->evenement->load([
            'cabinet',
            'dossier.client',
            'participants',
            'rappels.user',
            'calendarSyncLogs.calendarIntegration',
        ]);

        return [
            'notificationChannels' => NotificationChannelEnum::cases(),
            'rappels' => $this->evenement->rappels()->with('user')->latest('date_declenchement')->get(),
            'syncLogs' => $this->evenement->calendarSyncLogs()->latest()->get(),
        ];
    }

    private function loadExistingRappels(): void
    {
        $firstRappel = $this->evenement->rappels()->first();

        if ($firstRappel === null) {
            return;
        }

        $this->delai_minutes = $firstRappel->delai_minutes;
        $this->channels = $this->evenement->rappels()
            ->select('canal_notification')
            ->distinct()
            ->pluck('canal_notification')
            ->all();
    }
}; ?>

<div class="py-8">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">{{ $evenement->titre }}</h1>
                <p class="mt-1 text-sm text-gray-600">{{ $evenement->type_evenement->label() }} - {{ $evenement->date_debut->format('Y-m-d H:i') }}</p>
            </div>

            <div class="flex flex-wrap gap-3">
                <a href="{{ route('evenements.index') }}" wire:navigate class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700">{{ __('Retour') }}</a>
                @can('update', \App\Models\Rappel::class)
                    <button type="button" x-data="" x-on:click.prevent="$dispatch('open-modal', 'rappels-form')" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800">{{ __('Configurer les rappels') }}</button>
                @endcan
            </div>
        </div>

        <x-loading-spinner target="saveRappels" class="mb-4" />

        <div class="grid gap-6 lg:grid-cols-[0.95fr_1.05fr]">
            <section class="bg-white p-6 shadow-sm sm:rounded-lg">
                <h2 class="text-lg font-medium text-gray-900">{{ __('Details') }}</h2>

                <dl class="mt-5 grid gap-4 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="font-medium text-gray-500">{{ __('Statut') }}</dt>
                        <dd class="mt-1 text-gray-900">{{ $evenement->statut_evenement->label() }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">{{ __('Lieu') }}</dt>
                        <dd class="mt-1 text-gray-900">{{ $evenement->lieu ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">{{ __('Debut') }}</dt>
                        <dd class="mt-1 text-gray-900">{{ $evenement->date_debut->format('Y-m-d H:i') }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">{{ __('Fin') }}</dt>
                        <dd class="mt-1 text-gray-900">{{ $evenement->date_fin->format('Y-m-d H:i') }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="font-medium text-gray-500">{{ __('Dossier') }}</dt>
                        <dd class="mt-1 text-gray-900">
                            @if ($evenement->dossier)
                                {{ $evenement->dossier->numero_reference }} - {{ $evenement->dossier->titre_dossier }}
                            @else
                                -
                            @endif
                        </dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="font-medium text-gray-500">{{ __('Description') }}</dt>
                        <dd class="mt-1 whitespace-pre-line text-gray-900">{{ $evenement->description ?? '-' }}</dd>
                    </div>
                </dl>
            </section>

            <section class="space-y-6">
                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <h2 class="text-lg font-medium text-gray-900">{{ __('Participants') }}</h2>
                    <div class="mt-4 space-y-2 text-sm">
                        @forelse ($evenement->participants as $participant)
                            <div class="flex items-center justify-between border-b border-gray-100 py-2">
                                <span class="font-medium text-gray-900">{{ $participant->nom }}</span>
                                <span class="text-gray-600">{{ $participant->email }}</span>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500">{{ __('Aucun participant.') }}</p>
                        @endforelse
                    </div>
                </div>

                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <h2 class="text-lg font-medium text-gray-900">{{ __('Rappels') }}</h2>
                    <div class="mt-4 space-y-2 text-sm">
                        @forelse ($rappels as $rappel)
                            <div class="flex items-center justify-between border-b border-gray-100 py-2">
                                <span class="font-medium text-gray-900">{{ $rappel->user->nom }} - {{ $rappel->canal_notification->label() }}</span>
                                <span class="text-gray-600">{{ $rappel->date_declenchement->format('Y-m-d H:i') }}</span>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500">{{ __('Aucun rappel configure.') }}</p>
                        @endforelse
                    </div>
                </div>
            </section>
        </div>

        <section class="mt-6 bg-white p-6 shadow-sm sm:rounded-lg">
            <h2 class="text-lg font-medium text-gray-900">{{ __('Synchronisations calendrier') }}</h2>
            <div class="mt-4 space-y-2 text-sm">
                @forelse ($syncLogs as $syncLog)
                    <div class="flex items-center justify-between border-b border-gray-100 py-2">
                        <span class="font-medium text-gray-900">{{ $syncLog->provider->label() }}</span>
                        <span class="text-gray-600">{{ $syncLog->sync_status->label() }}</span>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">{{ __('Aucune synchronisation enregistree.') }}</p>
                @endforelse
            </div>
        </section>

        <x-modal name="rappels-form" maxWidth="2xl" focusable>
            <form wire:submit="saveRappels" class="space-y-5 p-6">
                <h2 class="text-lg font-medium text-gray-900">{{ __('Configurer les rappels') }}</h2>

                <div>
                    <x-input-label for="delai_minutes" :value="__('Delai avant evenement en minutes')" />
                    <x-text-input id="delai_minutes" wire:model="delai_minutes" type="number" min="1" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('delai_minutes')" class="mt-2" />
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach ($notificationChannels as $channel)
                        <label class="inline-flex items-center">
                            <input wire:model="channels" value="{{ $channel->value }}" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                            <span class="ms-2 text-sm text-gray-700">{{ $channel->label() }}</span>
                        </label>
                    @endforeach
                </div>
                <x-input-error :messages="$errors->get('channels')" class="mt-2" />

                <div class="flex items-center justify-end gap-3 border-t border-gray-100 pt-4">
                    <x-loading-spinner target="saveRappels" />
                    <x-secondary-button type="button" x-on:click="$dispatch('close')">{{ __('Annuler') }}</x-secondary-button>
                    <x-primary-button wire:loading.attr="disabled" wire:target="saveRappels">{{ __('Enregistrer') }}</x-primary-button>
                </div>
            </form>
        </x-modal>
    </div>
</div>
