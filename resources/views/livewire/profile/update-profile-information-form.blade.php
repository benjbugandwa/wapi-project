<?php

use App\DTO\UpdateProfileData;
use App\Services\UserAccessService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Volt\Component;

new class extends Component
{
    public string $nom = '';
    public string $email = '';
    public ?string $phone_number = null;

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->nom = Auth::user()->nom;
        $this->email = Auth::user()->email;
        $this->phone_number = Auth::user()->phone_number;
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(UserAccessService $userAccessService): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'nom' => ['required', 'string', 'max:150'],
            'phone_number' => ['nullable', 'string', 'max:50'],
        ]);

        $userAccessService->updateProfile($user, new UpdateProfileData(
            nom: $validated['nom'],
            phoneNumber: $validated['phone_number'] ?? null,
            profilePhotoPath: $user->profile_photo_path,
        ));

        notify()->success(__('Profil mis a jour.'), __('Succes'));

        $this->dispatch('profile-updated', name: $user->nom);
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function sendVerification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
        notify()->success(__('Un nouveau lien de verification a ete envoye.'), __('Succes'));
    }
}; ?>

<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Informations du profil') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('Mettez a jour les informations de votre profil.') }}
        </p>
    </header>

    <button
        type="button"
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'profile-information-form')"
        class="mt-6 rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800"
    >{{ __('Modifier') }}</button>

    <x-modal name="profile-information-form" maxWidth="2xl" focusable>
        <form wire:submit="updateProfileInformation" class="space-y-6 p-6">
            <h3 class="text-lg font-medium text-gray-900">{{ __('Modifier les informations du profil') }}</h3>

            <div>
            <x-input-label for="nom" :value="__('Nom complet')" />
            <x-text-input wire:model="nom" id="nom" name="nom" type="text" class="mt-1 block w-full" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('nom')" />
            </div>

            <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input wire:model="email" id="email" name="email" type="email" class="mt-1 block w-full bg-gray-100" disabled autocomplete="username" />
            <p class="mt-2 text-sm text-gray-500">{{ __('La modification de l email est reservee a un administrateur autorise.') }}</p>

            @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! auth()->user()->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-gray-800">
                        {{ __('Votre adresse email n est pas verifiee.') }}

                        <button wire:click.prevent="sendVerification" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            {{ __('Cliquez ici pour renvoyer l email de verification.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-600">
                            {{ __('Un nouveau lien de verification a ete envoye a votre adresse email.') }}
                        </p>
                    @endif
                </div>
            @endif
            </div>

            <div>
            <x-input-label for="phone_number" :value="__('Telephone')" />
            <x-text-input wire:model="phone_number" id="phone_number" name="phone_number" type="text" class="mt-1 block w-full" autocomplete="tel" />
            <x-input-error class="mt-2" :messages="$errors->get('phone_number')" />
            </div>

            <div class="flex items-center justify-end gap-4 border-t border-gray-100 pt-4">
            <x-loading-spinner target="updateProfileInformation" />

            <x-secondary-button type="button" x-on:click="$dispatch('close')">{{ __('Annuler') }}</x-secondary-button>
            <x-primary-button wire:loading.attr="disabled" wire:target="updateProfileInformation">{{ __('Enregistrer') }}</x-primary-button>

            <x-action-message class="me-3" on="profile-updated">
                {{ __('Enregistre.') }}
            </x-action-message>
            </div>
        </form>
    </x-modal>
</section>
