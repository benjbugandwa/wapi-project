<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Wapi</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

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

            <main class="mx-auto grid max-w-7xl gap-10 px-6 py-16 lg:grid-cols-[1.1fr_0.9fr] lg:items-center lg:py-24">
                <section>
                    <p class="text-sm font-medium uppercase tracking-[0.2em] text-indigo-300">
                        {{ __('SaaS multi-tenant pour cabinets d avocats') }}
                    </p>

                    <h1 class="mt-5 max-w-3xl text-4xl font-semibold tracking-tight sm:text-5xl">
                        {{ __('Centraliser la gestion du cabinet, des utilisateurs et des acces.') }}
                    </h1>

                    <p class="mt-6 max-w-2xl text-base leading-7 text-gray-300">
                        {{ __('Wapi pose les bases securisees de la gestion multi-cabinet : authentification, invitations, roles, permissions et isolation stricte des donnees.') }}
                    </p>

                    <div class="mt-8 flex flex-wrap gap-3">
                        <a href="{{ route('login') }}" class="rounded-md bg-white px-4 py-2 text-sm font-semibold text-gray-950 hover:bg-gray-100">
                            {{ __('Se connecter') }}
                        </a>
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
                            <dd class="mt-1 text-gray-300">{{ __('Invitation, activation, desactivation et attribution des roles Spatie.') }}</dd>
                        </div>

                        <div>
                            <dt class="font-medium text-white">{{ __('Securite') }}</dt>
                            <dd class="mt-1 text-gray-300">{{ __('Verification email, blocage des comptes inactifs et audit des actions sensibles.') }}</dd>
                        </div>
                    </dl>
                </section>
            </main>
        </div>
    </body>
</html>
