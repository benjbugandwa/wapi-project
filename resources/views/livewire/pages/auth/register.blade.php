<?php

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $nom = '';
    public string $email = '';
    public string $telephone = '';
    public string $phone_number = '';
    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Normaliser le numéro de téléphone pour garantir le format international +243XXXXXXXXX.
     */
    public function normalizePhoneNumber(string $value): string
    {
        $digits = preg_replace('/\D+/', '', $value) ?? '';

        if (str_starts_with($digits, '243') && strlen($digits) === 12) {
            return '+'.$digits;
        }

        if (str_starts_with($digits, '0') && strlen($digits) === 10) {
            return '+243'.substr($digits, 1);
        }

        if (strlen($digits) === 9) {
            return '+243'.$digits;
        }

        return ! empty($digits) ? '+243'.$digits : '';
    }

    /**
     * Handle an incoming registration request.
     */
    public function register(): void
    {
        $rawPhone = ! empty($this->telephone) ? $this->telephone : $this->phone_number;
        $normalizedPhone = $this->normalizePhoneNumber($rawPhone);

        $validator = Validator::make([
            'nom' => $this->nom,
            'email' => $this->email,
            'telephone' => $normalizedPhone,
            'password' => $this->password,
            'password_confirmation' => $this->password_confirmation,
        ], [
            'nom' => ['required', 'string', 'max:150'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'telephone' => [
                'required',
                'regex:/^\+243[0-9]{9}$/',
                Rule::unique('users', 'phone_number')->whereNull('deleted_at'),
            ],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ], [
            'telephone.required' => __('Le numéro de téléphone est obligatoire.'),
            'telephone.regex' => __('Le numéro de téléphone doit respecter le format +243 suivi de 9 chiffres (ex: 970480293).'),
            'telephone.unique' => __('Ce numéro de téléphone est déjà associé à un compte.'),
        ]);

        $validated = $validator->validate();

        $user = User::query()->forceCreate([
            'nom' => $validated['nom'],
            'email' => $validated['email'],
            'phone_number' => $validated['telephone'],
            'password' => Hash::make($validated['password']),
            'is_active' => false,
            'cabinet_id' => null,
        ]);

        event(new Registered($user));

        session()->flash('status', __('Votre compte a ete cree avec succes. Un administrateur va lui attribuer un role et un cabinet avant de l activer.'));
        notify()->success(__('Votre compte a ete cree avec succes. Un administrateur va lui attribuer un role et un cabinet avant de l activer.'), __('Succes'));

        $this->redirect(route('login', absolute: false), navigate: true);
    }
}; ?>

<div>
    <!-- Information banner -->
    <div class="mb-6 rounded-md border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800">
        <div class="flex">
            <div class="shrink-0">
                <svg class="h-5 w-5 text-blue-500" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ms-3">
                <h3 class="font-medium text-blue-900">{{ __('Attribution du role et du cabinet') }}</h3>
                <p class="mt-1 text-blue-700">
                    {{ __('Veuillez renseigner vos coordonnees. Votre role (Avocat, Comptable, etc.) et votre cabinet vous seront attribues ulterieurement par l administrateur systeme.') }}
                </p>
            </div>
        </div>
    </div>

    <form wire:submit="register">
        <!-- Nom complet -->
        <div>
            <x-input-label for="nom" :value="__('Nom complet')" />
            <x-text-input wire:model="nom" id="nom" class="block mt-1 w-full" type="text" name="nom" required autofocus autocomplete="name" placeholder="{{ __('ex: Jean Dupont') }}" />
            <x-input-error :messages="$errors->get('nom')" class="mt-2" />
        </div>

        <!-- Email -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('Adresse email')" />
            <x-text-input wire:model="email" id="email" class="block mt-1 w-full" type="email" name="email" required autocomplete="username" placeholder="{{ __('ex: jean.dupont@example.com') }}" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Numéro de téléphone -->
        <div class="mt-4">
            <x-input-label for="telephone" :value="__('Numéro de téléphone')" />
            <div class="mt-1 flex rounded-md shadow-xs">
                <span class="inline-flex items-center px-3.5 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-700 text-sm font-medium select-none">
                    🇨🇩 +243
                </span>
                <x-text-input wire:model="telephone" id="telephone"
                    class="rounded-l-none block w-full"
                    type="tel"
                    name="telephone"
                    required
                    autocomplete="tel-national"
                    placeholder="970480293" />
            </div>
            <p class="mt-1 text-xs text-gray-500">{{ __('Format attendu : 9 chiffres après +243 (ex: 970480293). Requis pour les notifications SMS.') }}</p>
            <x-input-error :messages="$errors->get('telephone')" class="mt-2" />
        </div>

        <!-- Mot de passe -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Mot de passe')" />
            <x-text-input wire:model="password" id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password"
                            placeholder="••••••••" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirmer le mot de passe -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirmer le mot de passe')" />
            <x-text-input wire:model="password_confirmation" id="password_confirmation" class="block mt-1 w-full"
                            type="password"
                            name="password_confirmation"
                            required autocomplete="new-password"
                            placeholder="••••••••" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between mt-6">
            <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('login') }}" wire:navigate>
                {{ __('Deja inscrit ? Se connecter') }}
            </a>

            <div class="flex items-center">
                <x-loading-spinner target="register" class="ms-4" />

                <x-primary-button class="ms-4" wire:loading.attr="disabled" wire:target="register">
                    {{ __('Creer mon compte') }}
                </x-primary-button>
            </div>
        </div>
    </form>
</div>
