<?php

use App\Models\Cabinet;
use App\Services\CabinetService;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public function mount(): void
    {
        Gate::authorize('viewAny', Cabinet::class);
    }

    public function suspend(string $cabinetId, CabinetService $cabinetService): void
    {
        $cabinet = Cabinet::query()->findOrFail($cabinetId);

        Gate::authorize('suspend', $cabinet);

        $cabinetService->suspend($cabinet, auth()->user());

        notify()->success(__('Cabinet suspendu.'), __('Succes'));
    }

    public function activate(string $cabinetId, CabinetService $cabinetService): void
    {
        $cabinet = Cabinet::query()->findOrFail($cabinetId);

        Gate::authorize('activate', $cabinet);

        $cabinetService->activate($cabinet, auth()->user());

        notify()->success(__('Cabinet reactive.'), __('Succes'));
    }

    public function with(): array
    {
        return [
            'cabinets' => Cabinet::query()
                ->withCount('users')
                ->latest()
                ->get(),
        ];
    }
}; ?>

<div class="py-8">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">{{ __('Cabinets') }}</h1>
                <p class="mt-1 text-sm text-gray-600">{{ __('Administration systeme des cabinets tenants.') }}</p>
            </div>

            <a href="{{ route('admin.cabinets.create') }}" wire:navigate class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800">
                {{ __('Creer') }}
            </a>
        </div>

        <x-loading-spinner target="suspend,activate" class="mb-4" />

        <div class="overflow-hidden border border-gray-200 bg-white shadow-sm sm:rounded-lg">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-4 py-3">{{ __('Denomination') }}</th>
                        <th class="px-4 py-3">{{ __('Repondant') }}</th>
                        <th class="px-4 py-3">{{ __('Utilisateurs') }}</th>
                        <th class="px-4 py-3">{{ __('Statut') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($cabinets as $cabinet)
                        <tr>
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $cabinet->denomination }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $cabinet->nom_repondant }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $cabinet->users_count }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700">{{ $cabinet->statut_cabinet->value }}</span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                @if ($cabinet->isActive())
                                    <button
                                        type="button"
                                        wire:click="suspend('{{ $cabinet->id }}')"
                                        wire:confirm="{{ __('Voulez-vous vraiment suspendre ce cabinet ?') }}"
                                        wire:loading.attr="disabled"
                                        class="text-sm font-medium text-amber-700 hover:text-amber-900"
                                    >{{ __('Suspendre') }}</button>
                                @else
                                    <button
                                        type="button"
                                        wire:click="activate('{{ $cabinet->id }}')"
                                        wire:confirm="{{ __('Voulez-vous vraiment reactiver ce cabinet ?') }}"
                                        wire:loading.attr="disabled"
                                        class="text-sm font-medium text-green-700 hover:text-green-900"
                                    >{{ __('Reactiver') }}</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-gray-500">{{ __('Aucun cabinet pour le moment.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
