<?php

use App\DTO\CreatePaiementData;
use App\Enums\MethodePaiementEnum;
use App\Models\Facture;
use App\Models\Paiement;
use App\Services\PaiementService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public Facture $facture;

    public string $montant_paye = '';
    public string $date_paiement = '';
    public string $methode_paiement = 'virement';
    public ?string $reference_transaction = null;

    public function mount(Facture $facture): void
    {
        Gate::authorize('view', $facture);

        $this->facture = $facture;
        $this->resetPaymentForm();
    }

    public function savePayment(PaiementService $paiementService): void
    {
        Gate::authorize('create', Paiement::class);
        Gate::authorize('view', $this->facture);

        $validated = $this->validate([
            'montant_paye' => ['required', 'numeric', 'min:0.01'],
            'date_paiement' => ['required', 'date'],
            'methode_paiement' => ['required', Rule::in(MethodePaiementEnum::values())],
            'reference_transaction' => ['nullable', 'string', 'max:150'],
        ]);

        $paiementService->create(new CreatePaiementData(
            factureId: $this->facture->id,
            montantPaye: number_format((float) $validated['montant_paye'], 2, '.', ''),
            datePaiement: CarbonImmutable::parse($validated['date_paiement']),
            methodePaiement: MethodePaiementEnum::from($validated['methode_paiement']),
            referenceTransaction: $validated['reference_transaction'] ?? null,
        ), auth()->user());

        $this->facture = $this->facture->refresh();
        $this->resetPaymentForm();
        $this->dispatch('close-modal', 'payment-form');

        notify()->success(__('Paiement enregistre.'), __('Succes'));
    }

    public function with(): array
    {
        $this->facture->load(['client', 'dossier', 'lignes', 'paiements.createdBy']);

        return [
            'methodesPaiement' => MethodePaiementEnum::cases(),
        ];
    }

    private function resetPaymentForm(): void
    {
        $this->montant_paye = number_format((float) $this->facture->remainingAmount(), 2, '.', '');
        $this->date_paiement = now()->toDateString();
        $this->methode_paiement = MethodePaiementEnum::Virement->value;
        $this->reference_transaction = null;
        $this->resetValidation();
    }
}; ?>

<div class="py-8">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">{{ __('Facture') }} {{ $facture->numero_facture }}</h1>
                <p class="mt-1 text-sm text-gray-600">{{ $facture->client->nom }} {{ $facture->client->prenom }} - {{ $facture->statut->label() }}</p>
            </div>

            <div class="flex flex-wrap gap-3">
                <a href="{{ route('factures.index') }}" wire:navigate class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700">{{ __('Retour') }}</a>
                @can('downloadPdf', $facture)
                    <a href="{{ route('factures.pdf', $facture) }}" target="_blank" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700">{{ __('PDF facture') }}</a>
                @endcan
                @can('create', \App\Models\Paiement::class)
                    @if (! $facture->isPaid() && ! $facture->isCancelled())
                        <button type="button" x-data="" x-on:click.prevent="$dispatch('open-modal', 'payment-form')" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800">{{ __('Enregistrer un paiement') }}</button>
                    @endif
                @endcan
            </div>
        </div>

        <x-loading-spinner target="savePayment" class="mb-4" />

        <div class="grid gap-6 lg:grid-cols-[0.9fr_1.1fr]">
            <section class="bg-white p-6 shadow-sm sm:rounded-lg">
                <h2 class="text-lg font-medium text-gray-900">{{ __('Informations') }}</h2>

                <dl class="mt-5 grid gap-4 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="font-medium text-gray-500">{{ __('Type') }}</dt>
                        <dd class="mt-1 text-gray-900">{{ $facture->type_facture->label() }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">{{ __('Statut') }}</dt>
                        <dd class="mt-1 text-gray-900">{{ $facture->statut->label() }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">{{ __('Emission') }}</dt>
                        <dd class="mt-1 text-gray-900">{{ $facture->date_emission->format('Y-m-d') }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">{{ __('Echeance') }}</dt>
                        <dd class="mt-1 text-gray-900">{{ $facture->date_echeance->format('Y-m-d') }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="font-medium text-gray-500">{{ __('Dossier') }}</dt>
                        <dd class="mt-1 text-gray-900">
                            @if ($facture->dossier)
                                {{ $facture->dossier->numero_reference }} - {{ $facture->dossier->titre_dossier }}
                            @else
                                -
                            @endif
                        </dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="font-medium text-gray-500">{{ __('Notes') }}</dt>
                        <dd class="mt-1 whitespace-pre-line text-gray-900">{{ $facture->notes_client ?? '-' }}</dd>
                    </div>
                </dl>
            </section>

            <section class="bg-white p-6 shadow-sm sm:rounded-lg">
                <h2 class="text-lg font-medium text-gray-900">{{ __('Situation') }}</h2>

                <dl class="mt-5 grid gap-4 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="font-medium text-gray-500">{{ __('Montant HT') }}</dt>
                        <dd class="mt-1 text-gray-900">{{ number_format((float) $facture->montant_ht, 2, ',', ' ') }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">{{ __('TVA') }}</dt>
                        <dd class="mt-1 text-gray-900">{{ number_format((float) $facture->montant_tva, 2, ',', ' ') }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">{{ __('Montant TTC') }}</dt>
                        <dd class="mt-1 text-gray-900">{{ number_format((float) $facture->montant_ttc, 2, ',', ' ') }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">{{ __('Reste du') }}</dt>
                        <dd class="mt-1 text-gray-900">{{ number_format((float) $facture->remainingAmount(), 2, ',', ' ') }}</dd>
                    </div>
                </dl>
            </section>
        </div>

        <section class="mt-6 bg-white p-6 shadow-sm sm:rounded-lg">
            <h2 class="text-lg font-medium text-gray-900">{{ __('Lignes de facture') }}</h2>
            <div class="mt-4 overflow-hidden rounded-md border border-gray-200">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-medium uppercase text-gray-500">
                        <tr>
                            <th class="px-4 py-3">{{ __('Description') }}</th>
                            <th class="px-4 py-3">{{ __('Quantite') }}</th>
                            <th class="px-4 py-3">{{ __('Prix unitaire') }}</th>
                            <th class="px-4 py-3">{{ __('Total HT') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($facture->lignes as $ligne)
                            <tr>
                                <td class="px-4 py-3 text-gray-900">{{ $ligne->description }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ number_format((float) $ligne->quantite, 2, ',', ' ') }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ number_format((float) $ligne->prix_unitaire, 2, ',', ' ') }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ number_format((float) $ligne->total_ligne_ht, 2, ',', ' ') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <section class="mt-6 bg-white p-6 shadow-sm sm:rounded-lg">
            <h2 class="text-lg font-medium text-gray-900">{{ __('Paiements') }}</h2>
            <div class="mt-4 overflow-hidden rounded-md border border-gray-200">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-medium uppercase text-gray-500">
                        <tr>
                            <th class="px-4 py-3">{{ __('Date') }}</th>
                            <th class="px-4 py-3">{{ __('Montant') }}</th>
                            <th class="px-4 py-3">{{ __('Methode') }}</th>
                            <th class="px-4 py-3">{{ __('Reference') }}</th>
                            <th class="px-4 py-3 text-right">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($facture->paiements as $paiement)
                            <tr>
                                <td class="px-4 py-3 text-gray-900">{{ $paiement->date_paiement->format('Y-m-d') }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ number_format((float) $paiement->montant_paye, 2, ',', ' ') }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $paiement->methode_paiement->label() }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $paiement->reference_transaction ?? '-' }}</td>
                                <td class="px-4 py-3 text-right">
                                    @can('downloadReceipt', $paiement)
                                        <a href="{{ route('paiements.receipt', $paiement) }}" target="_blank" class="text-sm font-medium text-gray-900 hover:text-gray-700">{{ __('Recu') }}</a>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-center text-gray-500">{{ __('Aucun paiement enregistre.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <x-modal name="payment-form" maxWidth="lg" focusable>
            <form wire:submit="savePayment" class="space-y-5 p-6">
                <h2 class="text-lg font-medium text-gray-900">{{ __('Enregistrer un paiement') }}</h2>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <x-input-label for="montant_paye" :value="__('Montant paye')" />
                        <x-text-input id="montant_paye" wire:model="montant_paye" type="number" step="0.01" class="mt-1 block w-full" />
                        <x-input-error :messages="$errors->get('montant_paye')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="date_paiement" :value="__('Date paiement')" />
                        <x-text-input id="date_paiement" wire:model="date_paiement" type="date" class="mt-1 block w-full" />
                        <x-input-error :messages="$errors->get('date_paiement')" class="mt-2" />
                    </div>
                </div>

                <div>
                    <x-input-label for="methode_paiement" :value="__('Methode')" />
                    <select id="methode_paiement" wire:model="methode_paiement" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @foreach ($methodesPaiement as $methode)
                            <option value="{{ $methode->value }}">{{ $methode->label() }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('methode_paiement')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="reference_transaction" :value="__('Reference transaction')" />
                    <x-text-input id="reference_transaction" wire:model="reference_transaction" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('reference_transaction')" class="mt-2" />
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-gray-100 pt-4">
                    <x-loading-spinner target="savePayment" />
                    <x-secondary-button type="button" x-on:click="$dispatch('close')">{{ __('Annuler') }}</x-secondary-button>
                    <x-primary-button wire:loading.attr="disabled" wire:target="savePayment">{{ __('Enregistrer') }}</x-primary-button>
                </div>
            </form>
        </x-modal>
    </div>
</div>
