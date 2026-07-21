<?php

use App\Enums\ClientTypeEnum;
use App\Models\Client;
use App\Services\ClientService;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component
{
    use WithPagination;

    public string $search = '';
    public string $type = '';

    public function mount(): void
    {
        Gate::authorize('viewAny', Client::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedType(): void
    {
        $this->resetPage();
    }

    public function delete(string $clientId, ClientService $clientService): void
    {
        $client = Client::query()->findOrFail($clientId);

        Gate::authorize('delete', $client);

        $clientService->delete($client, auth()->user());

        notify()->success(__('Client supprime.'), __('Succes'));
    }

    public function with(): array
    {
        $user = auth()->user();

        return [
            'types' => ClientTypeEnum::cases(),
            'clients' => Client::query()
                ->with(['cabinet', 'createdBy'])
                ->when(! $user->isSystemAdmin(), fn ($query) => $query->where('cabinet_id', $user->cabinet_id))
                ->when($this->search !== '', function ($query): void {
                    $query->where(function ($query): void {
                        $query->where('nom', 'like', '%'.$this->search.'%')
                            ->orWhere('prenom', 'like', '%'.$this->search.'%')
                            ->orWhere('email', 'like', '%'.$this->search.'%')
                            ->orWhere('telephone_principal', 'like', '%'.$this->search.'%');
                    });
                })
                ->when($this->type !== '', fn ($query) => $query->where('type_client', $this->type))
                ->latest()
                ->paginate(10),
        ];
    }
}; ?>

<div class="py-8">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">{{ __('Clients') }}</h1>
                <p class="mt-1 text-sm text-gray-600">{{ __('Rechercher, filtrer et consulter les clients du cabinet.') }}</p>
            </div>

            @can('create', \App\Models\Client::class)
                <a href="{{ route('clients.create') }}" wire:navigate class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800">
                    {{ __('Nouveau client') }}
                </a>
            @endcan
        </div>

        <x-loading-spinner target="search,type,delete" class="mb-4" />

        <div class="mb-4 grid gap-3 bg-white p-4 shadow-sm sm:rounded-lg md:grid-cols-3">
            <div class="md:col-span-2">
                <x-input-label for="search" :value="__('Recherche')" />
                <x-text-input id="search" wire:model.live.debounce.300ms="search" class="mt-1 block w-full" placeholder="{{ __('Nom, email ou telephone') }}" />
            </div>

            <div>
                <x-input-label for="type" :value="__('Type')" />
                <select id="type" wire:model.live="type" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">{{ __('Tous les types') }}</option>
                    @foreach ($types as $clientType)
                        <option value="{{ $clientType->value }}">{{ $clientType->label() }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="overflow-hidden border border-gray-200 bg-white shadow-sm sm:rounded-lg">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-4 py-3">{{ __('Client') }}</th>
                        <th class="px-4 py-3">{{ __('Type') }}</th>
                        <th class="px-4 py-3">{{ __('Contact') }}</th>
                        <th class="px-4 py-3">{{ __('Ville') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($clients as $client)
                        <tr>
                            <td class="px-4 py-3">
                                <a href="{{ route('clients.show', $client) }}" wire:navigate class="font-medium text-gray-900 hover:text-gray-700">
                                    {{ $client->nom }} @if ($client->prenom) {{ $client->prenom }} @endif
                                </a>
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ $client->type_client->label() }}</td>
                            <td class="px-4 py-3 text-gray-600">
                                <div>{{ $client->email ?? '-' }}</div>
                                <div class="text-xs text-gray-500">{{ $client->telephone_principal ?? '-' }}</div>
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ $client->ville ?? '-' }}</td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex justify-end gap-3">
                                    <a href="{{ route('clients.show', $client) }}" wire:navigate class="text-sm font-medium text-gray-900 hover:text-gray-700">{{ __('Voir') }}</a>
                                    @can('update', $client)
                                        <a href="{{ route('clients.edit', $client) }}" wire:navigate class="text-sm font-medium text-indigo-700 hover:text-indigo-900">{{ __('Modifier') }}</a>
                                    @endcan
                                    @can('delete', $client)
                                        <button
                                            type="button"
                                            wire:click="delete('{{ $client->id }}')"
                                            wire:confirm="{{ __('Voulez-vous vraiment supprimer ce client ?') }}"
                                            wire:loading.attr="disabled"
                                            class="text-sm font-medium text-red-700 hover:text-red-900"
                                        >{{ __('Supprimer') }}</button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-gray-500">{{ __('Aucun client trouve.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $clients->links() }}
        </div>
    </div>
</div>
