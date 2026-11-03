<?php

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    /**
     * Send an email verification notification to the user.
     */
    public function sendVerification(): void
    {
        if (Auth::user()->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);

            return;
        }

        Auth::user()->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
        notify()->success(__('Un nouveau lien de verification a ete envoye.'), __('Succes'));
    }

    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<div>
    <div class="mb-4 text-sm text-gray-600">
        {{ __('Merci pour votre inscription. Avant de continuer, verifiez votre adresse email en cliquant sur le lien que nous venons de vous envoyer. Si vous ne l avez pas recu, vous pouvez demander un nouvel envoi.') }}
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 font-medium text-sm text-green-600">
            {{ __('Un nouveau lien de verification a ete envoye a votre adresse email.') }}
        </div>
    @endif

    <div class="mt-4 flex items-center justify-between">
        <x-loading-spinner target="sendVerification" />

        <x-primary-button wire:click="sendVerification" wire:loading.attr="disabled" wire:target="sendVerification">
            {{ __('Renvoyer l email de verification') }}
        </x-primary-button>

        <button wire:click="logout" type="submit" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            {{ __('Deconnexion') }}
        </button>
    </div>
</div>
