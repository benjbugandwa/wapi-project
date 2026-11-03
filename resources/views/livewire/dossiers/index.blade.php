<?php

use App\Enums\DomaineDroitEnum;
use App\Enums\DossierStatusEnum;
use App\Models\Dossier;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statut = '';
    public string $domaine = '';

    public function mount(): void
    {
        Gate::authorize('viewAny', Dossier::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatut(): void
    {
        $this->resetPage();
    }

    public function updatedDomaine(): void
    {
        $this->resetPage();
    }

    public function with(): array
    {
        $user = auth()->user();

        return [
            'statuses' => DossierStatusEnum::cases(),
            'domaines' => DomaineDroitEnum::cases(),
            'dossiers' => Dossier::query()
                ->with(['client', 'cabinet'])
                ->when(! $user->isSystemAdmin(), fn ($query) => $query->where('cabinet_id', $user->cabinet_id))
                ->when($this->search !== '', function ($query): void {
                    $query->where(function ($query): void {
                        $query->where('numero_reference', 'like', '%'.$this->search.'%')
                            ->orWhere('titre_dossier', 'like', '%'.$this->search.'%')
                            ->orWhereHas('client', function ($query): void {
                                $query->where('nom', 'like', '%'.$this->search.'%')
                                    ->orWhere('prenom', 'like', '%'.$this->search.'%');
                            });
                    });
                })
                ->when($this->statut !== '', fn ($query) => $query->where('statut_dossier', $this->statut))
                ->when($this->domaine !== '', fn ($query) => $query->where('domaine_droit', $this->domaine))
                ->latest('date_ouverture')
                ->paginate(10),
        ];
    }
}; ?>

<div class="py-8">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">{{ __('Dossiers') }}</h1>
                <p class="mt-1 text-sm text-gray-600">{{ __('Suivre les dossiers, statuts, clients et collaborateurs du cabinet.') }}</p>
            </div>

            @can('create', \App\Models\Dossier::class)
                <a href="{{ route('dossiers.create') }}" wire:navigate class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800">
                    {{ __('Nouveau dossier') }}
                </a>
            @endcan
        </div>

        <x-loading-spinner target="search,statut,domaine" class="mb-4" />

        <div class="mb-4 grid gap-3 bg-white p-4 shadow-sm sm:rounded-lg md:grid-cols-4">
            <div class="md:col-span-2">
                <x-input-label for="search" :value="__('Recherche')" />
                <x-text-input id="search" wire:model.live.debounce.300ms="search" class="mt-1 block w-full" placeholder="{{ __('Reference, titre ou client') }}" />
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

            <div>
                <x-input-label for="domaine" :value="__('Domaine')" />
                <select id="domaine" wire:model.live="domaine" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">{{ __('Tous les domaines') }}</option>
                    @foreach ($domaines as $domaineDroit)
                        <option value="{{ $domaineDroit->value }}">{{ $domaineDroit->label() }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="overflow-hidden border border-gray-200 bg-white shadow-sm sm:rounded-lg">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-4 py-3">{{ __('Reference') }}</th>
                        <th class="px-4 py-3">{{ __('Dossier') }}</th>
                        <th class="px-4 py-3">{{ __('Client') }}</th>
                        <th class="px-4 py-3">{{ __('Statut') }}</th>
                        <th class="px-4 py-3">{{ __('Age') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($dossiers as $dossier)
                        <tr>
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $dossier->numero_reference }}</td>
                            <td class="px-4 py-3">
                                <a href="{{ route('dossiers.show', $dossier) }}" wire:navigate class="font-medium text-gray-900 hover:text-gray-700">
                                    {{ $dossier->titre_dossier }}
                                </a>
                                <div class="text-xs text-gray-500">{{ $dossier->domaine_droit->label() }}</div>
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ $dossier->client->nom }} @if ($dossier->client->prenom) {{ $dossier->client->prenom }} @endif</td>
                            <td class="px-4 py-3">
                                <span class="rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700">{{ $dossier->statut_dossier->label() }}</span>
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ $dossier->ageInDays() }} {{ __('jours') }}</td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex justify-end gap-3">
                                    <a href="{{ route('dossiers.show', $dossier) }}" wire:navigate class="text-sm font-medium text-gray-900 hover:text-gray-700">{{ __('Voir') }}</a>
                                    @can('update', $dossier)
                                        <a href="{{ route('dossiers.edit', $dossier) }}" wire:navigate class="text-sm font-medium text-indigo-700 hover:text-indigo-900">{{ __('Modifier') }}</a>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-gray-500">{{ __('Aucun dossier trouve.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $dossiers->links() }}
        </div>
    </div>
</div>
