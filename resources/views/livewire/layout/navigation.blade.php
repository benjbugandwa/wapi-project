<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component
{
    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}" wire:navigate>
                        <img src="{{ asset('images/logos/horizontal.png') }}" alt="{{ config('app.name', 'Wapi') }}" class="block h-9 w-auto">
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('Tableau de bord') }}
                    </x-nav-link>

                    @can('viewAny', \App\Models\Client::class)
                        <x-nav-link :href="route('clients.index')" :active="request()->routeIs('clients.*')" wire:navigate>
                            {{ __('Clients') }}
                        </x-nav-link>
                    @endcan

                    @can('viewAny', \App\Models\Dossier::class)
                        <x-nav-link :href="route('dossiers.index')" :active="request()->routeIs('dossiers.*')" wire:navigate>
                            {{ __('Dossiers') }}
                        </x-nav-link>
                    @endcan

                    @can('viewAny', \App\Models\Evenement::class)
                        <x-nav-link :href="route('evenements.index')" :active="request()->routeIs('evenements.*')" wire:navigate>
                            {{ __('Agenda') }}
                        </x-nav-link>
                    @endcan

                    @can('viewAny', \App\Models\Facture::class)
                        <x-nav-link :href="route('factures.index')" :active="request()->routeIs('factures.*') || request()->routeIs('paiements.*') || request()->routeIs('comptabilite.*')" wire:navigate>
                            {{ __('Facturation') }}
                        </x-nav-link>
                    @endcan

                    @can('viewAny', \App\Models\Cabinet::class)
                        <x-nav-link :href="route('admin.cabinets.index')" :active="request()->routeIs('admin.cabinets.*')" wire:navigate>
                            {{ __('Cabinets') }}
                        </x-nav-link>
                    @endcan

                    @can('viewAny', \App\Models\User::class)
                        <x-nav-link :href="route('admin.users.index')" :active="request()->routeIs('admin.users.*')" wire:navigate>
                            {{ __('Utilisateurs') }}
                        </x-nav-link>
                    @endcan
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                            <div x-data="{{ json_encode(['name' => auth()->user()->nom]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile')" wire:navigate>
                            {{ __('Profil') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <button wire:click="logout" class="w-full text-start">
                            <x-dropdown-link>
                                {{ __('Deconnexion') }}
                            </x-dropdown-link>
                        </button>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" wire:navigate>
                {{ __('Tableau de bord') }}
            </x-responsive-nav-link>

            @can('viewAny', \App\Models\Client::class)
                <x-responsive-nav-link :href="route('clients.index')" :active="request()->routeIs('clients.*')" wire:navigate>
                    {{ __('Clients') }}
                </x-responsive-nav-link>
            @endcan

            @can('viewAny', \App\Models\Dossier::class)
                <x-responsive-nav-link :href="route('dossiers.index')" :active="request()->routeIs('dossiers.*')" wire:navigate>
                    {{ __('Dossiers') }}
                </x-responsive-nav-link>
            @endcan

            @can('viewAny', \App\Models\Evenement::class)
                <x-responsive-nav-link :href="route('evenements.index')" :active="request()->routeIs('evenements.*')" wire:navigate>
                    {{ __('Agenda') }}
                </x-responsive-nav-link>
            @endcan

            @can('viewAny', \App\Models\Facture::class)
                <x-responsive-nav-link :href="route('factures.index')" :active="request()->routeIs('factures.*') || request()->routeIs('paiements.*') || request()->routeIs('comptabilite.*')" wire:navigate>
                    {{ __('Facturation') }}
                </x-responsive-nav-link>
            @endcan

            @can('viewAny', \App\Models\Cabinet::class)
                <x-responsive-nav-link :href="route('admin.cabinets.index')" :active="request()->routeIs('admin.cabinets.*')" wire:navigate>
                    {{ __('Cabinets') }}
                </x-responsive-nav-link>
            @endcan

            @can('viewAny', \App\Models\User::class)
                <x-responsive-nav-link :href="route('admin.users.index')" :active="request()->routeIs('admin.users.*')" wire:navigate>
                    {{ __('Utilisateurs') }}
                </x-responsive-nav-link>
            @endcan
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800" x-data="{{ json_encode(['name' => auth()->user()->nom]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></div>
                <div class="font-medium text-sm text-gray-500">{{ auth()->user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile')" wire:navigate>
                    {{ __('Profil') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <button wire:click="logout" class="w-full text-start">
                    <x-responsive-nav-link>
                        {{ __('Deconnexion') }}
                    </x-responsive-nav-link>
                </button>
            </div>
        </div>
    </div>
</nav>
