<?php

use App\DTO\SaveEvenementData;
use App\DTO\SaveNotificationPreferencesData;
use App\Enums\EvenementTypeEnum;
use App\Enums\NotificationChannelEnum;
use App\Enums\PermissionEnum;
use App\Models\Dossier;
use App\Models\Evenement;
use App\Models\NotificationPreference;
use App\Models\User;
use App\Services\EvenementService;
use App\Services\NotificationPreferenceService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component
{
    use WithPagination;

    public string $search = '';
    public string $type = '';

    public ?string $editing_id = null;
    public ?string $dossier_id = null;
    public string $titre = '';
    public string $type_evenement = 'rendez_vous';
    public string $date_debut = '';
    public string $date_fin = '';
    public ?string $lieu = null;
    public ?string $description = null;

    /**
     * @var array<int, string>
     */
    public array $participant_ids = [];

    /**
     * @var array<string, bool>
     */
    public array $preference_enabled = [];

    /**
     * @var array<string, string|null>
     */
    public array $preference_destinations = [];

    public function mount(): void
    {
        Gate::authorize('viewAny', Evenement::class);

        $this->loadPreferences();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedType(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        Gate::authorize('create', Evenement::class);

        $this->resetForm();
        $this->dispatch('open-modal', 'agenda-form');
    }

    public function edit(string $evenementId): void
    {
        $evenement = Evenement::query()->with('participants')->findOrFail($evenementId);

        Gate::authorize('update', $evenement);

        $this->editing_id = $evenement->id;
        $this->dossier_id = $evenement->dossier_id;
        $this->titre = $evenement->titre;
        $this->type_evenement = $evenement->type_evenement->value;
        $this->date_debut = $evenement->date_debut->format('Y-m-d\TH:i');
        $this->date_fin = $evenement->date_fin->format('Y-m-d\TH:i');
        $this->lieu = $evenement->lieu;
        $this->description = $evenement->description;
        $this->participant_ids = $evenement->participants->pluck('id')->all();

        $this->dispatch('open-modal', 'agenda-form');
    }

    public function save(EvenementService $evenementService): void
    {
        $validated = $this->validate([
            'dossier_id' => ['nullable', 'uuid', Rule::exists('dossiers', 'id')->where('cabinet_id', auth()->user()->cabinet_id)],
            'titre' => ['required', 'string', 'max:255'],
            'type_evenement' => ['required', Rule::in(EvenementTypeEnum::values())],
            'date_debut' => ['required', 'date'],
            'date_fin' => ['required', 'date', 'after:date_debut'],
            'lieu' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'participant_ids' => ['array'],
            'participant_ids.*' => ['uuid', Rule::exists('users', 'id')->where('cabinet_id', auth()->user()->cabinet_id)],
        ]);

        $data = new SaveEvenementData(
            titre: $validated['titre'],
            typeEvenement: EvenementTypeEnum::from($validated['type_evenement']),
            dateDebut: CarbonImmutable::parse($validated['date_debut']),
            dateFin: CarbonImmutable::parse($validated['date_fin']),
            dossierId: $validated['dossier_id'] ?? null,
            lieu: $validated['lieu'] ?? null,
            description: $validated['description'] ?? null,
            participantIds: $validated['participant_ids'] ?? [],
        );

        if ($this->editing_id === null) {
            Gate::authorize('create', Evenement::class);
            $evenementService->create($data, auth()->user());
            notify()->success(__('Evenement cree.'), __('Succes'));
        } else {
            $evenement = Evenement::query()->findOrFail($this->editing_id);
            Gate::authorize('update', $evenement);
            $evenementService->update($evenement, $data, auth()->user());
            notify()->success(__('Evenement mis a jour.'), __('Succes'));
        }

        $this->resetForm();
        $this->dispatch('close-modal', 'agenda-form');
    }

    public function cancel(string $evenementId, EvenementService $evenementService): void
    {
        $evenement = Evenement::query()->findOrFail($evenementId);

        Gate::authorize('cancel', $evenement);

        $evenementService->cancel($evenement, auth()->user());

        notify()->success(__('Evenement annule.'), __('Succes'));
    }

    public function savePreferences(NotificationPreferenceService $preferenceService): void
    {
        Gate::authorize('manage', NotificationPreference::class);

        $validated = $this->validate([
            'preference_enabled' => ['array'],
            'preference_enabled.*' => ['boolean'],
            'preference_destinations' => ['array'],
            'preference_destinations.*' => ['nullable', 'string', 'max:255'],
        ]);

        $preferenceService->save(auth()->user(), new SaveNotificationPreferencesData(
            enabledChannels: $validated['preference_enabled'] ?? [],
            destinations: $validated['preference_destinations'] ?? [],
        ));

        auth()->user()->load('notificationPreferences');
        $this->loadPreferences();
        $this->dispatch('close-modal', 'notification-preferences-form');

        notify()->success(__('Preferences de notification mises a jour.'), __('Succes'));
    }

    public function with(): array
    {
        $user = auth()->user();

        return [
            'types' => EvenementTypeEnum::cases(),
            'channels' => NotificationChannelEnum::cases(),
            'dossiers' => Dossier::query()
                ->when(! $user->isSystemAdmin(), fn ($query) => $query->where('cabinet_id', $user->cabinet_id))
                ->orderBy('numero_reference')
                ->get(),
            'availableUsers' => User::query()
                ->where('cabinet_id', $user->cabinet_id)
                ->orderBy('nom')
                ->get(),
            'evenements' => Evenement::query()
                ->with(['dossier', 'participants'])
                ->when(! $user->isSystemAdmin(), function ($query) use ($user): void {
                    $query->where('cabinet_id', $user->cabinet_id)
                        ->when(! $user->can(PermissionEnum::ShareAgenda->value), function ($query) use ($user): void {
                            $query->where(function ($query) use ($user): void {
                                $query->where('created_by', $user->id)
                                    ->orWhereHas('participants', fn ($query) => $query->whereKey($user->id));
                            });
                        });
                })
                ->when($this->search !== '', fn ($query) => $query->where('titre', 'like', '%'.$this->search.'%'))
                ->when($this->type !== '', fn ($query) => $query->where('type_evenement', $this->type))
                ->where('date_debut', '>=', now()->subDay())
                ->orderBy('date_debut')
                ->paginate(10),
        ];
    }

    private function resetForm(): void
    {
        $this->editing_id = null;
        $this->dossier_id = null;
        $this->titre = '';
        $this->type_evenement = EvenementTypeEnum::RendezVous->value;
        $this->date_debut = now()->addDay()->setTime(9, 0)->format('Y-m-d\TH:i');
        $this->date_fin = now()->addDay()->setTime(10, 0)->format('Y-m-d\TH:i');
        $this->lieu = null;
        $this->description = null;
        $this->participant_ids = [auth()->id()];
        $this->resetValidation();
    }

    private function loadPreferences(): void
    {
        auth()->user()->load('notificationPreferences');

        foreach (NotificationChannelEnum::cases() as $channel) {
            $preference = auth()->user()->notificationPreferences
                ->first(fn (NotificationPreference $preference): bool => $preference->canal_notification === $channel);

            $this->preference_enabled[$channel->value] = $preference?->is_enabled ?? in_array($channel, [NotificationChannelEnum::Email, NotificationChannelEnum::InApp], true);
            $this->preference_destinations[$channel->value] = $preference?->destination ?? match ($channel) {
                NotificationChannelEnum::Email => auth()->user()->email,
                NotificationChannelEnum::Sms, NotificationChannelEnum::WhatsApp => auth()->user()->phone_number,
                default => null,
            };
        }
    }
}; ?>

<div class="py-8">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">{{ __('Agenda') }}</h1>
                <p class="mt-1 text-sm text-gray-600">{{ __('Visualiser les audiences, rendez-vous et rappels du cabinet.') }}</p>
            </div>

            <div class="flex flex-wrap gap-3">
                @can('manage', \App\Models\NotificationPreference::class)
                    <button type="button" x-data="" x-on:click.prevent="$dispatch('open-modal', 'notification-preferences-form')" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700">{{ __('Notifications') }}</button>
                @endcan
                @can('create', \App\Models\Evenement::class)
                    <button type="button" wire:click="create" wire:loading.attr="disabled" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800">{{ __('Nouvel evenement') }}</button>
                @endcan
            </div>
        </div>

        <x-loading-spinner target="search,type,save,cancel,savePreferences,create,edit" class="mb-4" />

        <div class="mb-4 grid gap-3 bg-white p-4 shadow-sm sm:rounded-lg md:grid-cols-3">
            <div class="md:col-span-2">
                <x-input-label for="search" :value="__('Recherche')" />
                <x-text-input id="search" wire:model.live.debounce.300ms="search" class="mt-1 block w-full" placeholder="{{ __('Titre evenement') }}" />
            </div>

            <div>
                <x-input-label for="type" :value="__('Type')" />
                <select id="type" wire:model.live="type" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">{{ __('Tous les types') }}</option>
                    @foreach ($types as $typeOption)
                        <option value="{{ $typeOption->value }}">{{ $typeOption->label() }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="overflow-hidden border border-gray-200 bg-white shadow-sm sm:rounded-lg">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-4 py-3">{{ __('Evenement') }}</th>
                        <th class="px-4 py-3">{{ __('Date') }}</th>
                        <th class="px-4 py-3">{{ __('Dossier') }}</th>
                        <th class="px-4 py-3">{{ __('Participants') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($evenements as $evenement)
                        <tr>
                            <td class="px-4 py-3">
                                <a href="{{ route('evenements.show', $evenement) }}" wire:navigate class="font-medium text-gray-900 hover:text-gray-700">{{ $evenement->titre }}</a>
                                <div class="text-xs text-gray-500">{{ $evenement->type_evenement->label() }}</div>
                            </td>
                            <td class="px-4 py-3 text-gray-600">
                                {{ $evenement->date_debut->format('Y-m-d H:i') }}
                                <div class="text-xs text-gray-500">{{ __('Fin') }} {{ $evenement->date_fin->format('Y-m-d H:i') }}</div>
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ $evenement->dossier?->numero_reference ?? '-' }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $evenement->participants->pluck('nom')->join(', ') ?: '-' }}</td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex justify-end gap-3">
                                    <a href="{{ route('evenements.show', $evenement) }}" wire:navigate class="text-sm font-medium text-gray-900 hover:text-gray-700">{{ __('Voir') }}</a>
                                    @can('update', $evenement)
                                        <button type="button" wire:click="edit('{{ $evenement->id }}')" class="text-sm font-medium text-indigo-700 hover:text-indigo-900">{{ __('Modifier') }}</button>
                                    @endcan
                                    @can('cancel', $evenement)
                                        <button type="button" wire:click="cancel('{{ $evenement->id }}')" wire:confirm="{{ __('Voulez-vous vraiment annuler cet evenement ?') }}" wire:loading.attr="disabled" class="text-sm font-medium text-red-700 hover:text-red-900">{{ __('Annuler') }}</button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-gray-500">{{ __('Aucun evenement a venir.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $evenements->links() }}</div>

        <x-modal name="agenda-form" maxWidth="2xl" focusable>
            <form wire:submit="save" class="space-y-5 p-6">
                <h2 class="text-lg font-medium text-gray-900">{{ $editing_id ? __('Modifier un evenement') : __('Nouvel evenement') }}</h2>

                <div>
                    <x-input-label for="titre" :value="__('Titre')" />
                    <x-text-input id="titre" wire:model="titre" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('titre')" class="mt-2" />
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <x-input-label for="type_evenement" :value="__('Type')" />
                        <select id="type_evenement" wire:model="type_evenement" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach ($types as $typeOption)
                                <option value="{{ $typeOption->value }}">{{ $typeOption->label() }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <x-input-label for="dossier_id" :value="__('Dossier lie')" />
                        <select id="dossier_id" wire:model="dossier_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">{{ __('Aucun dossier') }}</option>
                            @foreach ($dossiers as $dossier)
                                <option value="{{ $dossier->id }}">{{ $dossier->numero_reference }} - {{ $dossier->titre_dossier }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <x-input-label for="date_debut" :value="__('Debut')" />
                        <x-text-input id="date_debut" wire:model="date_debut" type="datetime-local" class="mt-1 block w-full" />
                        <x-input-error :messages="$errors->get('date_debut')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="date_fin" :value="__('Fin')" />
                        <x-text-input id="date_fin" wire:model="date_fin" type="datetime-local" class="mt-1 block w-full" />
                        <x-input-error :messages="$errors->get('date_fin')" class="mt-2" />
                    </div>
                </div>

                <div>
                    <x-input-label for="lieu" :value="__('Lieu')" />
                    <x-text-input id="lieu" wire:model="lieu" class="mt-1 block w-full" />
                </div>

                <div>
                    <x-input-label for="participant_ids" :value="__('Participants')" />
                    <select id="participant_ids" wire:model="participant_ids" multiple class="mt-1 block min-h-32 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @foreach ($availableUsers as $availableUser)
                            <option value="{{ $availableUser->id }}">{{ $availableUser->nom }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('participant_ids')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="description" :value="__('Description')" />
                    <textarea id="description" wire:model="description" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-gray-100 pt-4">
                    <x-loading-spinner target="save" />
                    <x-secondary-button type="button" x-on:click="$dispatch('close')">{{ __('Annuler') }}</x-secondary-button>
                    <x-primary-button wire:loading.attr="disabled" wire:target="save">{{ __('Enregistrer') }}</x-primary-button>
                </div>
            </form>
        </x-modal>

        <x-modal name="notification-preferences-form" maxWidth="2xl" focusable>
            <form wire:submit="savePreferences" class="space-y-5 p-6">
                <h2 class="text-lg font-medium text-gray-900">{{ __('Preferences de notification') }}</h2>

                @foreach ($channels as $channel)
                    <div class="grid gap-3 border-b border-gray-100 pb-3 sm:grid-cols-[160px_1fr] sm:items-center">
                        <label class="inline-flex items-center">
                            <input wire:model="preference_enabled.{{ $channel->value }}" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                            <span class="ms-2 text-sm font-medium text-gray-700">{{ $channel->label() }}</span>
                        </label>

                        <x-text-input wire:model="preference_destinations.{{ $channel->value }}" class="block w-full" placeholder="{{ __('Destination optionnelle') }}" />
                    </div>
                @endforeach

                <div class="flex items-center justify-end gap-3 border-t border-gray-100 pt-4">
                    <x-loading-spinner target="savePreferences" />
                    <x-secondary-button type="button" x-on:click="$dispatch('close')">{{ __('Annuler') }}</x-secondary-button>
                    <x-primary-button wire:loading.attr="disabled" wire:target="savePreferences">{{ __('Enregistrer') }}</x-primary-button>
                </div>
            </form>
        </x-modal>
    </div>
</div>
