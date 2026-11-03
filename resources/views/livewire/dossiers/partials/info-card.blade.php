<section class="bg-white p-6 shadow-sm sm:rounded-lg">
    <div class="flex items-center justify-between gap-3">
        <h2 class="text-lg font-medium text-gray-900">{{ __('Informations dossier') }}</h2>
        <span class="rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700">{{ $dossier->statut_dossier->label() }}</span>
    </div>

    <dl class="mt-5 grid gap-4 text-sm sm:grid-cols-2">
        <div>
            <dt class="font-medium text-gray-500">{{ __('Domaine') }}</dt>
            <dd class="mt-1 text-gray-900">{{ $dossier->domaine_droit->label() }}</dd>
        </div>
        <div>
            <dt class="font-medium text-gray-500">{{ __('Age') }}</dt>
            <dd class="mt-1 text-gray-900">{{ $dossier->ageInDays() }} {{ __('jours') }}</dd>
        </div>
        <div>
            <dt class="font-medium text-gray-500">{{ __('Date d ouverture') }}</dt>
            <dd class="mt-1 text-gray-900">{{ $dossier->date_ouverture->format('Y-m-d') }}</dd>
        </div>
        <div>
            <dt class="font-medium text-gray-500">{{ __('Date de cloture') }}</dt>
            <dd class="mt-1 text-gray-900">{{ $dossier->date_cloture?->format('Y-m-d') ?? '-' }}</dd>
        </div>
        <div>
            <dt class="font-medium text-gray-500">{{ __('Juridiction') }}</dt>
            <dd class="mt-1 text-gray-900">{{ $dossier->juridiction ?? '-' }}</dd>
        </div>
        <div>
            <dt class="font-medium text-gray-500">{{ __('Facturation') }}</dt>
            <dd class="mt-1 text-gray-900">{{ $dossier->type_facturation->label() }}</dd>
        </div>
        <div class="sm:col-span-2">
            <dt class="font-medium text-gray-500">{{ __('Description') }}</dt>
            <dd class="mt-1 whitespace-pre-line text-gray-900">{{ $dossier->description ?? '-' }}</dd>
        </div>
    </dl>
</section>
