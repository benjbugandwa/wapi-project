<?php

use App\Enums\PermissionEnum;
use App\Models\Client;
use App\Services\ComptabiliteReportService;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component
{
    use WithPagination;

    public ?string $client_id = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->can(PermissionEnum::ViewComptabiliteReports->value), 403);
    }

    public function updatedClientId(): void
    {
        $this->resetPage('situationPage');
    }

    public function with(ComptabiliteReportService $reportService): array
    {
        $user = auth()->user();

        return [
            'clients' => Client::query()
                ->when(! $user->isSystemAdmin(), fn (Builder $query) => $query->where('cabinet_id', $user->cabinet_id))
                ->orderBy('nom')
                ->get(),
            'facturesEnSouffrance' => $reportService->overdueInvoices($user),
            'situationClient' => $reportService->clientSituation($user, $this->client_id),
        ];
    }
}; ?>

<div class="py-8">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">{{ __('Rapports comptables') }}</h1>
                <p class="mt-1 text-sm text-gray-600">{{ __('Suivre les echeances, soldes et paiements par client.') }}</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('factures.index') }}" wire:navigate class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700">{{ __('Factures') }}</a>
                <a href="{{ route('paiements.index') }}" wire:navigate class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700">{{ __('Paiements') }}</a>
            </div>
        </div>

        <section class="bg-white p-6 shadow-sm sm:rounded-lg">
            <h2 class="text-lg font-medium text-gray-900">{{ __('Factures en souffrance') }}</h2>
            <div class="mt-4 overflow-hidden rounded-md border border-gray-200">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-medium uppercase text-gray-500">
                        <tr>
                            <th class="px-4 py-3">{{ __('Facture') }}</th>
                            <th class="px-4 py-3">{{ __('Client') }}</th>
                            <th class="px-4 py-3">{{ __('Echeance') }}</th>
                            <th class="px-4 py-3">{{ __('Statut') }}</th>
                            <th class="px-4 py-3">{{ __('Reste du') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($facturesEnSouffrance as $facture)
                            <tr>
                                <td class="px-4 py-3">
                                    <a href="{{ route('factures.show', $facture) }}" wire:navigate class="font-medium text-gray-900 hover:text-gray-700">{{ $facture->numero_facture }}</a>
                                </td>
                                <td class="px-4 py-3 text-gray-600">{{ $facture->client->nom }} {{ $facture->client->prenom }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $facture->date_echeance->format('Y-m-d') }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $facture->statut->label() }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ number_format((float) $facture->remainingAmount(), 2, ',', ' ') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-center text-gray-500">{{ __('Aucune facture en souffrance.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $facturesEnSouffrance->links() }}</div>
        </section>

        <section class="mt-6 bg-white p-6 shadow-sm sm:rounded-lg">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h2 class="text-lg font-medium text-gray-900">{{ __('Situation client') }}</h2>
                    <p class="mt-1 text-sm text-gray-600">{{ __('Factures, paiements et soldes par client.') }}</p>
                </div>
                <div class="w-full sm:max-w-xs">
                    <x-input-label for="client_id" :value="__('Client')" />
                    <select id="client_id" wire:model.live="client_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">{{ __('Tous les clients') }}</option>
                        @foreach ($clients as $client)
                            <option value="{{ $client->id }}">{{ $client->nom }} {{ $client->prenom }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mt-4 overflow-hidden rounded-md border border-gray-200">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-medium uppercase text-gray-500">
                        <tr>
                            <th class="px-4 py-3">{{ __('Facture') }}</th>
                            <th class="px-4 py-3">{{ __('Client') }}</th>
                            <th class="px-4 py-3">{{ __('Emission') }}</th>
                            <th class="px-4 py-3">{{ __('Total') }}</th>
                            <th class="px-4 py-3">{{ __('Paye') }}</th>
                            <th class="px-4 py-3">{{ __('Reste du') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($situationClient as $facture)
                            <tr>
                                <td class="px-4 py-3">
                                    <a href="{{ route('factures.show', $facture) }}" wire:navigate class="font-medium text-gray-900 hover:text-gray-700">{{ $facture->numero_facture }}</a>
                                </td>
                                <td class="px-4 py-3 text-gray-600">{{ $facture->client->nom }} {{ $facture->client->prenom }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $facture->date_emission->format('Y-m-d') }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ number_format((float) $facture->montant_ttc, 2, ',', ' ') }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ number_format((float) $facture->paidAmount(), 2, ',', ' ') }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ number_format((float) $facture->remainingAmount(), 2, ',', ' ') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-6 text-center text-gray-500">{{ __('Aucune facture pour cette selection.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $situationClient->links() }}</div>
        </section>
    </div>
</div>
