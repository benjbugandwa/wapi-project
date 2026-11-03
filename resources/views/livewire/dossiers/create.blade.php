<?php

use App\DTO\CreateDossierData;
use App\Enums\DomaineDroitEnum;
use App\Enums\TypeFacturationEnum;
use App\Models\Client;
use App\Models\Dossier;
use App\Services\DossierService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public ?string $client_id = null;
    public string $numero_reference = '';
    public string $titre_dossier = '';
    public ?string $description = null;
    public string $domaine_droit = 'autre';
    public string $date_ouverture = '';
    public ?string $juridiction = null;
    public string $type_facturation = 'forfait';

    public function mount(): void
    {
        Gate::authorize('create', Dossier::class);

        $this->date_ouverture = now()->toDateString();
    }

    public function save(DossierService $dossierService): void
    {
        $user = auth()->user();

        $validated = $this->validate([
            'client_id' => [
                'required',
                'uuid',
                Rule::exists('clients', 'id')->where('cabinet_id', $user->cabinet_id),
            ],
            'numero_reference' => [
                'required',
                'string',
                'max:50',
                Rule::unique('dossiers', 'numero_reference')->where('cabinet_id', $user->cabinet_id),
            ],
            'titre_dossier' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'domaine_droit' => ['required', Rule::in(DomaineDroitEnum::values())],
            'date_ouverture' => ['required', 'date'],
            'juridiction' => ['nullable', 'string', 'max:150'],
            'type_facturation' => ['required', Rule::in(TypeFacturationEnum::values())],
        ]);

        $dossier = $dossierService->create(new CreateDossierData(
            clientId: $validated['client_id'],
            numeroReference: $validated['numero_reference'],
            titreDossier: $validated['titre_dossier'],
            domaineDroit: DomaineDroitEnum::from($validated['domaine_droit']),
            dateOuverture: CarbonImmutable::parse($validated['date_ouverture']),
            typeFacturation: TypeFacturationEnum::from($validated['type_facturation']),
            description: $validated['description'] ?? null,
            juridiction: $validated['juridiction'] ?? null,
        ), $user);

        notify()->success(__('Dossier cree.'), __('Succes'));

        $this->redirectRoute('dossiers.show', $dossier, navigate: true);
    }

    public function with(): array
    {
        return [
            'clients' => Client::query()
                ->where('cabinet_id', auth()->user()->cabinet_id)
                ->orderBy('nom')
                ->get(),
            'domaines' => DomaineDroitEnum::cases(),
            'typesFacturation' => TypeFacturationEnum::cases(),
        ];
    }
}; ?>

<div class="py-8">
    <x-modal name="dossier-create-form" :show="true" maxWidth="2xl" :closeable="false" focusable>
        <form wire:submit="save" class="space-y-5 p-6">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">{{ __('Nouveau dossier') }}</h1>
                <p class="mt-1 text-sm text-gray-600">{{ __('Liez le dossier a un client du cabinet et renseignez les informations principales.') }}</p>
            </div>

            <div>
                <x-input-label for="client_id" :value="__('Client')" />
                <select id="client_id" wire:model="client_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">{{ __('Selectionner un client') }}</option>
                    @foreach ($clients as $client)
                        <option value="{{ $client->id }}">{{ $client->nom }} @if ($client->prenom) {{ $client->prenom }} @endif</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('client_id')" class="mt-2" />
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <x-input-label for="numero_reference" :value="__('Reference')" />
                    <x-text-input id="numero_reference" wire:model="numero_reference" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('numero_reference')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="date_ouverture" :value="__('Date d ouverture')" />
                    <x-text-input id="date_ouverture" wire:model="date_ouverture" type="date" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('date_ouverture')" class="mt-2" />
                </div>
            </div>

            <div>
                <x-input-label for="titre_dossier" :value="__('Titre du dossier')" />
                <x-text-input id="titre_dossier" wire:model="titre_dossier" class="mt-1 block w-full" />
                <x-input-error :messages="$errors->get('titre_dossier')" class="mt-2" />
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <x-input-label for="domaine_droit" :value="__('Domaine de droit')" />
                    <select id="domaine_droit" wire:model="domaine_droit" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @foreach ($domaines as $domaine)
                            <option value="{{ $domaine->value }}">{{ $domaine->label() }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('domaine_droit')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="type_facturation" :value="__('Type de facturation')" />
                    <select id="type_facturation" wire:model="type_facturation" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @foreach ($typesFacturation as $typeFacturation)
                            <option value="{{ $typeFacturation->value }}">{{ $typeFacturation->label() }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('type_facturation')" class="mt-2" />
                </div>
            </div>

            <div>
                <x-input-label for="juridiction" :value="__('Juridiction')" />
                <x-text-input id="juridiction" wire:model="juridiction" class="mt-1 block w-full" />
                <x-input-error :messages="$errors->get('juridiction')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="description" :value="__('Description')" />
                <textarea id="description" wire:model="description" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                <x-input-error :messages="$errors->get('description')" class="mt-2" />
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-gray-100 pt-4">
                <x-loading-spinner target="save" />
                <a href="{{ route('dossiers.index') }}" wire:navigate class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700">{{ __('Annuler') }}</a>
                <x-primary-button wire:loading.attr="disabled" wire:target="save">{{ __('Enregistrer') }}</x-primary-button>
            </div>
        </form>
    </x-modal>
</div>
