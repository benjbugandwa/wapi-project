<?php

use App\DTO\SaveFactureData;
use App\Enums\FactureStatusEnum;
use App\Enums\FactureTypeEnum;
use App\Models\Client;
use App\Models\Dossier;
use App\Models\Facture;
use App\Services\FactureService;
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
    public string $statut = '';

    public ?string $editing_id = null;
    public string $client_id = '';
    public ?string $dossier_id = null;
    public string $type_facture = 'definitive';
    public string $date_emission = '';
    public string $date_echeance = '';
    public string $taux_tva = '0.00';
    public ?string $notes_client = null;

    /**
     * @var array<int, array{description: string, quantite: string, prix_unitaire: string}>
     */
    public array $lignes = [];

    public function mount(): void
    {
        Gate::authorize('viewAny', Facture::class);
        $this->resetForm();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatut(): void
    {
        $this->resetPage();
    }

    public function updatedClientId(): void
    {
        $this->dossier_id = null;
    }

    public function create(): void
    {
        Gate::authorize('create', Facture::class);

        $this->resetForm();
        $this->dispatch('open-modal', 'facture-form');
    }

    public function edit(string $factureId): void
    {
        $facture = Facture::query()->with('lignes')->findOrFail($factureId);

        Gate::authorize('update', $facture);

        $this->editing_id = $facture->id;
        $this->client_id = $facture->client_id;
        $this->dossier_id = $facture->dossier_id;
        $this->type_facture = $facture->type_facture->value;
        $this->date_emission = $facture->date_emission->format('Y-m-d');
        $this->date_echeance = $facture->date_echeance->format('Y-m-d');
        $this->taux_tva = (string) $facture->taux_tva;
        $this->notes_client = $facture->notes_client;
        $this->lignes = $facture->lignes->map(fn ($ligne): array => [
            'description' => $ligne->description,
            'quantite' => (string) $ligne->quantite,
            'prix_unitaire' => (string) $ligne->prix_unitaire,
        ])->all();

        $this->dispatch('open-modal', 'facture-form');
    }

    public function addLine(): void
    {
        $this->lignes[] = ['description' => '', 'quantite' => '1.00', 'prix_unitaire' => '0.00'];
    }

    public function removeLine(int $index): void
    {
        unset($this->lignes[$index]);
        $this->lignes = array_values($this->lignes);
    }

    public function save(FactureService $factureService): void
    {
        $validated = $this->validate([
            'client_id' => ['required', 'uuid', Rule::exists('clients', 'id')->where('cabinet_id', auth()->user()->cabinet_id)],
            'dossier_id' => ['nullable', 'uuid', Rule::exists('dossiers', 'id')->where('cabinet_id', auth()->user()->cabinet_id)],
            'type_facture' => ['required', Rule::in(FactureTypeEnum::values())],
            'date_emission' => ['required', 'date'],
            'date_echeance' => ['required', 'date', 'after_or_equal:date_emission'],
            'taux_tva' => ['required', 'numeric', 'min:0', 'max:100'],
            'notes_client' => ['nullable', 'string', 'max:5000'],
            'lignes' => ['required', 'array', 'min:1'],
            'lignes.*.description' => ['required', 'string', 'max:1000'],
            'lignes.*.quantite' => ['required', 'numeric', 'min:0.01'],
            'lignes.*.prix_unitaire' => ['required', 'numeric', 'min:0'],
        ]);

        $data = new SaveFactureData(
            clientId: $validated['client_id'],
            dossierId: $validated['dossier_id'] ?? null,
            typeFacture: FactureTypeEnum::from($validated['type_facture']),
            dateEmission: CarbonImmutable::parse($validated['date_emission']),
            dateEcheance: CarbonImmutable::parse($validated['date_echeance']),
            tauxTva: number_format((float) $validated['taux_tva'], 2, '.', ''),
            notesClient: $validated['notes_client'] ?? null,
            lignes: array_map(fn (array $ligne): array => [
                'description' => $ligne['description'],
                'quantite' => number_format((float) $ligne['quantite'], 2, '.', ''),
                'prix_unitaire' => number_format((float) $ligne['prix_unitaire'], 2, '.', ''),
            ], $validated['lignes']),
        );

        if ($this->editing_id === null) {
            Gate::authorize('create', Facture::class);
            $factureService->create($data, auth()->user());
            notify()->success(__('Facture creee.'), __('Succes'));
        } else {
            $facture = Facture::query()->findOrFail($this->editing_id);
            Gate::authorize('update', $facture);
            $factureService->update($facture, $data, auth()->user());
            notify()->success(__('Facture mise a jour.'), __('Succes'));
        }

        $this->resetForm();
        $this->dispatch('close-modal', 'facture-form');
    }

    public function send(string $factureId, FactureService $factureService): void
    {
        $facture = Facture::query()->findOrFail($factureId);

        Gate::authorize('send', $facture);

        $factureService->send($facture, auth()->user());

        notify()->success(__('Facture emise.'), __('Succes'));
    }

    public function cancel(string $factureId, FactureService $factureService): void
    {
        $facture = Facture::query()->findOrFail($factureId);

        Gate::authorize('cancel', $facture);

        $factureService->cancel($facture, auth()->user());

        notify()->success(__('Facture annulee.'), __('Succes'));
    }

    public function with(): array
    {
        $user = auth()->user();

        return [
            'types' => FactureTypeEnum::cases(),
            'statuses' => FactureStatusEnum::cases(),
            'clients' => Client::query()
                ->when(! $user->isSystemAdmin(), fn ($query) => $query->where('cabinet_id', $user->cabinet_id))
                ->orderBy('nom')
                ->get(),
            'dossiers' => Dossier::query()
                ->when(! $user->isSystemAdmin(), fn ($query) => $query->where('cabinet_id', $user->cabinet_id))
                ->when($this->client_id !== '', fn ($query) => $query->where('client_id', $this->client_id))
                ->orderBy('numero_reference')
                ->get(),
            'factures' => Facture::query()
                ->with(['client', 'dossier'])
                ->when(! $user->isSystemAdmin(), fn ($query) => $query->where('cabinet_id', $user->cabinet_id))
                ->when($this->search !== '', function ($query): void {
                    $query->where(function ($query): void {
                        $query->where('numero_facture', 'like', '%'.$this->search.'%')
                            ->orWhereHas('client', fn ($query) => $query->where('nom', 'like', '%'.$this->search.'%'));
                    });
                })
                ->when($this->statut !== '', fn ($query) => $query->where('statut', $this->statut))
                ->latest('date_emission')
                ->paginate(10),
        ];
    }

    private function resetForm(): void
    {
        $this->editing_id = null;
        $this->client_id = '';
        $this->dossier_id = null;
        $this->type_facture = FactureTypeEnum::Definitive->value;
        $this->date_emission = now()->toDateString();
        $this->date_echeance = now()->addDays(30)->toDateString();
        $this->taux_tva = '0.00';
        $this->notes_client = null;
        $this->lignes = [['description' => '', 'quantite' => '1.00', 'prix_unitaire' => '0.00']];
        $this->resetValidation();
    }
}; ?>

<div class="py-8">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">{{ __('Factures') }}</h1>
                <p class="mt-1 text-sm text-gray-600">{{ __('Suivre les factures, echeances et paiements du cabinet.') }}</p>
            </div>
            <div class="flex flex-wrap gap-3">
                @can('viewAny', \App\Models\Paiement::class)
                    <a href="{{ route('paiements.index') }}" wire:navigate class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700">{{ __('Paiements') }}</a>
                @endcan
                @can('create', \App\Models\Facture::class)
                    <button type="button" wire:click="create" wire:loading.attr="disabled" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800">{{ __('Nouvelle facture') }}</button>
                @endcan
            </div>
        </div>

        <x-loading-spinner target="search,statut,save,send,cancel,create,edit" class="mb-4" />

        <div class="mb-4 grid gap-3 bg-white p-4 shadow-sm sm:rounded-lg md:grid-cols-3">
            <div class="md:col-span-2">
                <x-input-label for="search" :value="__('Recherche')" />
                <x-text-input id="search" wire:model.live.debounce.300ms="search" class="mt-1 block w-full" placeholder="{{ __('Numero ou client') }}" />
            </div>
            <div>
                <x-input-label for="statut" :value="__('Statut')" />
                <select id="statut" wire:model.live="statut" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">{{ __('Tous les statuts') }}</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}">{{ $status->label() }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="overflow-hidden border border-gray-200 bg-white shadow-sm sm:rounded-lg">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-4 py-3">{{ __('Numero') }}</th>
                        <th class="px-4 py-3">{{ __('Client') }}</th>
                        <th class="px-4 py-3">{{ __('Statut') }}</th>
                        <th class="px-4 py-3">{{ __('TTC') }}</th>
                        <th class="px-4 py-3">{{ __('Reste du') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($factures as $facture)
                        <tr>
                            <td class="px-4 py-3">
                                <a href="{{ route('factures.show', $facture) }}" wire:navigate class="font-medium text-gray-900 hover:text-gray-700">{{ $facture->numero_facture }}</a>
                                <div class="text-xs text-gray-500">{{ $facture->date_echeance->format('Y-m-d') }}</div>
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ $facture->client->nom }} {{ $facture->client->prenom }}</td>
                            <td class="px-4 py-3"><span class="rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700">{{ $facture->statut->label() }}</span></td>
                            <td class="px-4 py-3 text-gray-600">{{ number_format((float) $facture->montant_ttc, 2, ',', ' ') }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ number_format((float) $facture->remainingAmount(), 2, ',', ' ') }}</td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex justify-end gap-3">
                                    <a href="{{ route('factures.show', $facture) }}" wire:navigate class="text-sm font-medium text-gray-900 hover:text-gray-700">{{ __('Voir') }}</a>
                                    @can('update', $facture)
                                        <button type="button" wire:click="edit('{{ $facture->id }}')" class="text-sm font-medium text-indigo-700 hover:text-indigo-900">{{ __('Modifier') }}</button>
                                    @endcan
                                    @can('send', $facture)
                                        <button type="button" wire:click="send('{{ $facture->id }}')" wire:loading.attr="disabled" class="text-sm font-medium text-gray-900 hover:text-gray-700">{{ __('Emettre') }}</button>
                                    @endcan
                                    @can('cancel', $facture)
                                        <button type="button" wire:click="cancel('{{ $facture->id }}')" wire:confirm="{{ __('Voulez-vous vraiment annuler cette facture ?') }}" wire:loading.attr="disabled" class="text-sm font-medium text-red-700 hover:text-red-900">{{ __('Annuler') }}</button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-gray-500">{{ __('Aucune facture trouvee.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $factures->links() }}</div>

        <x-modal name="facture-form" maxWidth="2xl" focusable>
            <form wire:submit="save" class="space-y-5 p-6">
                <h2 class="text-lg font-medium text-gray-900">{{ $editing_id ? __('Modifier une facture') : __('Nouvelle facture') }}</h2>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <x-input-label for="client_id" :value="__('Client')" />
                        <select id="client_id" wire:model="client_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">{{ __('Selectionner') }}</option>
                            @foreach ($clients as $client)
                                <option value="{{ $client->id }}">{{ $client->nom }} {{ $client->prenom }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('client_id')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="dossier_id" :value="__('Dossier')" />
                        <select id="dossier_id" wire:model="dossier_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">{{ __('Aucun dossier') }}</option>
                            @foreach ($dossiers as $dossier)
                                <option value="{{ $dossier->id }}">{{ $dossier->numero_reference }} - {{ $dossier->titre_dossier }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('dossier_id')" class="mt-2" />
                    </div>
                </div>

                <div class="grid gap-5 sm:grid-cols-4">
                    <div>
                        <x-input-label for="type_facture" :value="__('Type')" />
                        <select id="type_facture" wire:model="type_facture" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach ($types as $type)
                                <option value="{{ $type->value }}">{{ $type->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="date_emission" :value="__('Emission')" />
                        <x-text-input id="date_emission" wire:model="date_emission" type="date" class="mt-1 block w-full" />
                    </div>
                    <div>
                        <x-input-label for="date_echeance" :value="__('Echeance')" />
                        <x-text-input id="date_echeance" wire:model="date_echeance" type="date" class="mt-1 block w-full" />
                    </div>
                    <div>
                        <x-input-label for="taux_tva" :value="__('TVA %')" />
                        <x-text-input id="taux_tva" wire:model="taux_tva" type="number" step="0.01" class="mt-1 block w-full" />
                    </div>
                </div>

                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-medium text-gray-900">{{ __('Lignes') }}</h3>
                        <button type="button" wire:click="addLine" class="text-sm font-medium text-gray-900">{{ __('Ajouter une ligne') }}</button>
                    </div>
                    @foreach ($lignes as $index => $ligne)
                        <div class="grid gap-3 rounded-md border border-gray-200 p-3 sm:grid-cols-[1fr_100px_140px_80px]">
                            <x-text-input wire:model="lignes.{{ $index }}.description" placeholder="{{ __('Description') }}" />
                            <x-text-input wire:model="lignes.{{ $index }}.quantite" type="number" step="0.01" placeholder="{{ __('Quantite') }}" />
                            <x-text-input wire:model="lignes.{{ $index }}.prix_unitaire" type="number" step="0.01" placeholder="{{ __('Prix') }}" />
                            <button type="button" wire:click="removeLine({{ $index }})" class="text-sm font-medium text-red-700">{{ __('Retirer') }}</button>
                        </div>
                    @endforeach
                    <x-input-error :messages="$errors->get('lignes')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="notes_client" :value="__('Notes client')" />
                    <textarea id="notes_client" wire:model="notes_client" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-gray-100 pt-4">
                    <x-loading-spinner target="save" />
                    <x-secondary-button type="button" x-on:click="$dispatch('close')">{{ __('Annuler') }}</x-secondary-button>
                    <x-primary-button wire:loading.attr="disabled" wire:target="save">{{ __('Enregistrer') }}</x-primary-button>
                </div>
            </form>
        </x-modal>
    </div>
</div>
