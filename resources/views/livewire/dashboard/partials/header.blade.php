<div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <div class="flex items-center gap-3">
            <h1 class="text-2xl font-bold tracking-tight text-gray-900">
                {{ __('Bonjour') }}, {{ auth()->user()->nom }}
            </h1>
            <span class="inline-flex items-center rounded-full bg-indigo-50 px-3 py-1 text-xs font-medium text-indigo-700 ring-1 ring-inset ring-indigo-700/10">
                {{ \App\Enums\RoleEnum::tryFrom($currentRole)?->label() ?? __('Utilisateur') }}
            </span>
        </div>
        <p class="mt-1 text-sm text-gray-600">
            @if (auth()->user()->isSystemAdmin())
                {{ __('Supervision globale de la plateforme SaaS Wapi.') }}
            @else
                {{ auth()->user()->cabinet?->denomination ?? __('Cabinet') }} &bull; {{ now()->translatedFormat('l j F Y') }}
            @endif
        </p>
    </div>

    <div class="flex flex-wrap items-center gap-3">
        @if (auth()->user()->isSystemAdmin() || auth()->user()->roles->count() > 1 || auth()->user()->hasRole(\App\Enums\RoleEnum::Admin->value))
            <div class="flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-1.5 shadow-sm">
                <span class="text-xs font-medium text-gray-500">{{ __('Vue :') }}</span>
                <select
                    wire:change="switchRole($event.target.value)"
                    class="border-0 bg-transparent py-0 pl-1 pr-6 text-sm font-semibold text-gray-900 focus:ring-0 cursor-pointer"
                >
                    @if (auth()->user()->isSystemAdmin())
                        <option value="{{ \App\Enums\RoleEnum::SystemAdmin->value }}" @selected($currentRole === \App\Enums\RoleEnum::SystemAdmin->value)>
                            {{ __('Admin Système') }}
                        </option>
                    @endif
                    @if (auth()->user()->isSystemAdmin() || auth()->user()->hasRole(\App\Enums\RoleEnum::Admin->value))
                        <option value="{{ \App\Enums\RoleEnum::Admin->value }}" @selected($currentRole === \App\Enums\RoleEnum::Admin->value)>
                            {{ __('Admin Cabinet') }}
                        </option>
                    @endif
                    @if (auth()->user()->isSystemAdmin() || auth()->user()->hasRole(\App\Enums\RoleEnum::Avocat->value) || auth()->user()->hasRole(\App\Enums\RoleEnum::Admin->value))
                        <option value="{{ \App\Enums\RoleEnum::Avocat->value }}" @selected($currentRole === \App\Enums\RoleEnum::Avocat->value)>
                            {{ __('Avocat') }}
                        </option>
                    @endif
                    @if (auth()->user()->isSystemAdmin() || auth()->user()->hasRole(\App\Enums\RoleEnum::Comptable->value) || auth()->user()->hasRole(\App\Enums\RoleEnum::Admin->value))
                        <option value="{{ \App\Enums\RoleEnum::Comptable->value }}" @selected($currentRole === \App\Enums\RoleEnum::Comptable->value)>
                            {{ __('Comptable') }}
                        </option>
                    @endif
                </select>
            </div>
        @endif

        <x-loading-spinner target="switchRole,suspendCabinet,activateCabinet" />
    </div>
</div>
