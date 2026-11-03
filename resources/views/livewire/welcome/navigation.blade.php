<nav class="-mx-3 flex flex-1 items-center justify-end gap-3">
    @auth
        <a
            href="{{ url('/dashboard') }}"
            class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 transition"
        >
            {{ __('Tableau de bord') }}
        </a>
    @else
        <a
            href="{{ route('login') }}"
            class="inline-flex items-center rounded-md border border-white/30 bg-white/10 px-4 py-2 text-sm font-semibold text-white shadow-xs hover:bg-white/20 focus:outline-none focus-visible:ring-2 focus-visible:ring-white transition"
        >
            {{ __('Connexion') }}
        </a>

        @if (Route::has('register'))
            <a
                href="{{ route('register') }}"
                class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 transition"
            >
                {{ __('Créer un compte') }}
            </a>
        @endif
    @endauth
</nav>
