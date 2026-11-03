<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Wapi</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @notifyCss
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-950 text-white">
            <header class="mx-auto flex max-w-7xl items-center justify-between px-6 py-6">
                <a href="{{ url('/') }}">
                    <img src="{{ asset('images/logos/on_dark_background.png') }}" alt="Wapi" class="h-10 w-auto">
                </a>

                @if (Route::has('login'))
                    <livewire:welcome.navigation />
                @endif
            </header>

            @if (session('status'))
                <div class="mx-auto max-w-7xl px-6 pt-2">
                    <div class="rounded-xl border border-emerald-500/40 bg-emerald-950/80 p-4 text-emerald-200 shadow-lg flex items-center gap-3">
                        <div class="shrink-0 flex items-center justify-center w-8 h-8 rounded-full bg-emerald-500/20 text-emerald-400">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <p class="font-semibold text-sm sm:text-base text-white">
                                {{ session('status') }}
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            <main class="mx-auto grid max-w-7xl gap-10 px-6 py-16 lg:grid-cols-[1.1fr_0.9fr] lg:items-center lg:py-24">
                <section>
                    <p class="text-sm font-medium uppercase tracking-[0.2em] text-indigo-300">
                        {{ __('SaaS multi-tenant pour cabinets d avocats') }}
                    </p>

                    <h1 class="mt-5 max-w-3xl text-4xl font-semibold tracking-tight sm:text-5xl">
                        {{ __('Centraliser la gestion du cabinet, des utilisateurs et des acces.') }}
                    </h1>

                    <p class="mt-6 max-w-2xl text-base leading-7 text-gray-300">
                        {{ __('Wapi pose les bases securisees de la gestion multi-cabinet : authentification, roles, permissions et isolation stricte des donnees.') }}
                    </p>

                    <div class="mt-8 flex flex-wrap gap-3">
                        <a href="{{ route('login') }}" class="inline-flex items-center rounded-md bg-white px-5 py-2.5 text-sm font-semibold text-gray-950 shadow-sm hover:bg-gray-100 transition">
                            {{ __('Se connecter') }}
                        </a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="inline-flex items-center rounded-md border border-indigo-500 bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 transition">
                                {{ __('Créer un compte') }}
                            </a>
                        @endif
                    </div>
                </section>

                <section class="rounded-lg border border-white/10 bg-white/5 p-6">
                    <h2 class="text-lg font-semibold">{{ __('Fonctionnalites disponibles') }}</h2>

                    <dl class="mt-6 space-y-5 text-sm">
                        <div>
                            <dt class="font-medium text-white">{{ __('Cabinets') }}</dt>
                            <dd class="mt-1 text-gray-300">{{ __('Creation, suspension et reactivation par administrateur systeme.') }}</dd>
                        </div>

                        <div>
                            <dt class="font-medium text-white">{{ __('Utilisateurs') }}</dt>
                            <dd class="mt-1 text-gray-300">{{ __('Creation de compte, activation, desactivation et attribution des roles Spatie.') }}</dd>
                        </div>

                        <div>
                            <dt class="font-medium text-white">{{ __('Securite') }}</dt>
                            <dd class="mt-1 text-gray-300">{{ __('Verification email, blocage des comptes inactifs et audit des actions sensibles.') }}</dd>
                        </div>
                    </dl>
                </section>
            </main>
        </div>

        <x-notify::notify />
        @notifyJs
    </body>
</html>
