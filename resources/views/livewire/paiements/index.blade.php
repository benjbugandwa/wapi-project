<?php

use App\Models\Paiement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component
{
    use WithPagination;

    public string $search = '';

    public function mount(): void
    {
        Gate::authorize('viewAny', Paiement::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function with(): array
    {
        $user = auth()->user();

        return [
            'paiements' => Paiement::query()
                ->with(['facture.client', 'createdBy'])
                ->when(! $user->isSystemAdmin(), fn (Builder $query) => $query->where('cabinet_id', $user->cabinet_id))
                ->when($this->search !== '', function (Builder $query): void {
                    $query->where(function (Builder $query): void {
                        $query->where('reference_transaction', 'like', '%'.$this->search.'%')
                            ->orWhereHas('facture', fn (Builder $query) => $query->where('numero_facture', 'like', '%'.$this->search.'%'))
                            ->orWhereHas('facture.client', fn (Builder $query) => $query->where('nom', 'like', '%'.$this->search.'%'));
                    });
                })
                ->latest('date_paiement')
                ->paginate(15),
        ];
    }
}; ?>

<div class="py-8">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">{{ __('Paiements') }}</h1>
                <p class="mt-1 text-sm text-gray-600">{{ __('Consulter les paiements et generer les recus.') }}</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('factures.index') }}" wire:navigate class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700">{{ __('Factures') }}</a>
                @can('viewAny', \App\Models\Facture::class)
                    <a href="{{ route('comptabilite.rapports') }}" wire:navigate class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700">{{ __('Rapports') }}</a>
                @endcan
            </div>
        </div>

        <div class="mb-4 max-w-md">
            <x-input-label for="search" :value="__('Recherche')" />
            <x-text-input id="search" wire:model.live.debounce.300ms="search" class="mt-1 block w-full" placeholder="{{ __('Facture, client ou reference') }}" />
        </div>

        <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-medium uppercase text-gray-500">
                    <tr>
                        <th class="px-4 py-3">{{ __('Facture') }}</th>
                        <th class="px-4 py-3">{{ __('Client') }}</th>
                        <th class="px-4 py-3">{{ __('Date') }}</th>
                        <th class="px-4 py-3">{{ __('Montant') }}</th>
                        <th class="px-4 py-3">{{ __('Methode') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($paiements as $paiement)
                        <tr>
                            <td class="px-4 py-3">
                                <a href="{{ route('factures.show', $paiement->facture) }}" wire:navigate class="font-medium text-gray-900 hover:text-gray-700">{{ $paiement->facture->numero_facture }}</a>
                                <div class="text-xs text-gray-500">{{ $paiement->reference_transaction ?? '-' }}</div>
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ $paiement->facture->client->nom }} {{ $paiement->facture->client->prenom }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $paiement->date_paiement->format('Y-m-d') }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ number_format((float) $paiement->montant_paye, 2, ',', ' ') }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $paiement->methode_paiement->label() }}</td>
                            <td class="px-4 py-3 text-right">
                                @can('downloadReceipt', $paiement)
                                    <a href="{{ route('paiements.receipt', $paiement) }}" target="_blank" class="text-sm font-medium text-gray-900 hover:text-gray-700">{{ __('Recu') }}</a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-gray-500">{{ __('Aucun paiement trouve.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $paiements->links() }}</div>
    </div>
</div>
