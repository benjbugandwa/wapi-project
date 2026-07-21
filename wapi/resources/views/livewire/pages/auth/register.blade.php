<?php

use App\DTO\AcceptCabinetInvitationData;
use App\Services\CabinetInvitationService;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $nom = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';
    public ?string $token = null;
    public ?string $cabinetName = null;
    public bool $invitationFound = false;

    public function mount(?string $token = null): void
    {
        $this->token = $token;

        if ($token === null) {
            return;
        }

        $invitation = app(CabinetInvitationService::class)->findPendingByToken($token);

        if ($invitation === null) {
            return;
        }

        $this->email = $invitation->email;
        $this->cabinetName = $invitation->cabinet->denomination;
        $this->invitationFound = true;
    }

    /**
     * Handle an incoming registration request.
     */
    public function register(CabinetInvitationService $cabinetInvitationService): void
    {
        $validated = $this->validate([
            'nom' => ['required', 'string', 'max:150'],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
            'token' => ['required', 'string'],
        ]);

        $cabinetInvitationService->accept(new AcceptCabinetInvitationData(
            token: $validated['token'],
            nom: $validated['nom'],
            password: $validated['password'],
        ));

        session()->flash('status', __('Votre compte a ete cree. Il doit maintenant etre active par un administrateur.'));
        notify()->success(__('Votre compte a ete cree. Il doit maintenant etre active par un administrateur.'), __('Succes'));

        $this->redirect(route('login', absolute: false), navigate: true);
    }
}; ?>

<div>
    @if (! $invitationFound)
        <div class="mb-6 rounded-md border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
            {{ __('L inscription necessite une invitation cabinet valide.') }}
        </div>
    @endif

    <form wire:submit="register">
        <input type="hidden" wire:model="token" name="token">

        @if ($cabinetName)
            <div class="mb-4 rounded-md border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700">
                {{ __('Cabinet') }}: <span class="font-medium">{{ $cabinetName }}</span>
            </div>
        @endif

        <!-- Nom -->
        <div>
            <x-input-label for="nom" :value="__('Nom complet')" />
            <x-text-input wire:model="nom" id="nom" class="block mt-1 w-full" type="text" name="nom" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('nom')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input wire:model="email" id="email" class="block mt-1 w-full bg-gray-100" type="email" name="email" disabled autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Mot de passe')" />

            <x-text-input wire:model="password" id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirmer le mot de passe')" />

            <x-text-input wire:model="password_confirmation" id="password_confirmation" class="block mt-1 w-full"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('login') }}" wire:navigate>
                {{ __('Deja inscrit ?') }}
            </a>

            <x-loading-spinner target="register" class="ms-4" />

            <x-primary-button class="ms-4" :disabled="! $invitationFound" wire:loading.attr="disabled" wire:target="register">
                {{ __('S inscrire') }}
            </x-primary-button>
        </div>
    </form>
</div>
