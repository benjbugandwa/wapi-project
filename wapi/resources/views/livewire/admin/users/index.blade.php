<?php

use App\DTO\CreateUserData;
use App\DTO\UpdateUserRoleData;
use App\Enums\CabinetStatus;
use App\Enums\RoleEnum;
use App\Models\Cabinet;
use App\Models\User;
use App\Services\UserAccessService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    /**
     * @var array<string, string>
     */
    public array $roleUpdates = [];

    public string $nom = '';
    public string $email = '';
    public string $password = '';
    public string $role_name = RoleEnum::Avocat->value;
    public ?string $cabinet_id = '';

    public function mount(): void
    {
        Gate::authorize('viewAny', User::class);

        $this->roleUpdates = $this->usersQuery()
            ->get()
            ->mapWithKeys(fn (User $user): array => [$user->id => $user->roles->first()?->name ?? RoleEnum::Avocat->value])
            ->all();
    }

    public function openCreateModal(): void
    {
        Gate::authorize('create', User::class);

        $this->resetCreateForm();
        $this->dispatch('open-modal', 'create-user-modal');
    }

    public function resetCreateForm(): void
    {
        $this->resetErrorBag();
        $this->nom = '';
        $this->email = '';
        $this->password = '';
        $this->role_name = RoleEnum::Avocat->value;
        $this->cabinet_id = '';
    }

    public function createUser(UserAccessService $userAccessService): void
    {
        Gate::authorize('create', User::class);

        $isSystemAdminRole = $this->role_name === RoleEnum::SystemAdmin->value;

        $validated = $this->validate([
            'nom' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role_name' => ['required', 'string', Rule::in(RoleEnum::values())],
            'cabinet_id' => [
                $isSystemAdminRole ? 'nullable' : 'required',
                'uuid',
                Rule::exists('cabinets', 'id')->where('statut_cabinet', CabinetStatus::Active->value),
            ],
        ], [
            'cabinet_id.required' => __('Veuillez sélectionner un cabinet actif pour cet utilisateur.'),
        ]);

        $createdUser = $userAccessService->createUser(new CreateUserData(
            nom: $validated['nom'],
            email: $validated['email'],
            password: $validated['password'],
            roleName: $validated['role_name'],
            cabinetId: ! empty($validated['cabinet_id']) ? $validated['cabinet_id'] : null,
            isActive: true,
        ), auth()->user());

        $this->roleUpdates[$createdUser->id] = $validated['role_name'];

        $this->resetCreateForm();
        $this->dispatch('close-modal', 'create-user-modal');

        notify()->success(__('Utilisateur créé avec succès.'), __('Succès'));
    }

    public function activate(string $userId, UserAccessService $userAccessService): void
    {
        $target = User::query()->with(['cabinet', 'roles'])->findOrFail($userId);

        Gate::authorize('activate', $target);

        $userAccessService->activate($target, auth()->user());

        notify()->success(__('Utilisateur active.'), __('Succes'));
    }

    public function deactivate(string $userId, UserAccessService $userAccessService): void
    {
        $target = User::query()->with(['cabinet', 'roles'])->findOrFail($userId);

        Gate::authorize('deactivate', $target);

        $userAccessService->deactivate($target, auth()->user());

        notify()->success(__('Utilisateur desactive.'), __('Succes'));
    }

    public function updateRole(string $userId, UserAccessService $userAccessService): void
    {
        $target = User::query()->with(['cabinet', 'roles'])->findOrFail($userId);

        Gate::authorize('updateRole', $target);

        $validated = $this->validate([
            "roleUpdates.$userId" => [
                'required',
                'string',
                Rule::in($this->assignableRoleValues()),
            ],
        ]);

        $userAccessService->updateRole(new UpdateUserRoleData(
            target: $target,
            roleName: $validated['roleUpdates'][$userId],
        ), auth()->user());

        notify()->success(__('Role mis a jour.'), __('Succes'));
    }

    public function with(): array
    {
        return [
            'users' => $this->usersQuery()->with(['cabinet', 'roles'])->orderBy('nom')->get(),
            'roles' => collect(RoleEnum::cases())
                ->reject(fn (RoleEnum $role): bool => $role === RoleEnum::SystemAdmin && ! auth()->user()->isSystemAdmin())
                ->all(),
            'activeCabinets' => Cabinet::query()
                ->where('statut_cabinet', CabinetStatus::Active)
                ->orderBy('denomination')
                ->get(),
        ];
    }

    private function usersQuery()
    {
        $user = auth()->user();

        return User::query()
            ->with(['cabinet', 'roles'])
            ->when(! $user->isSystemAdmin(), fn ($query) => $query->where('cabinet_id', $user->cabinet_id));
    }

    /**
     * @return list<string>
     */
    private function assignableRoleValues(): array
    {
        return collect(RoleEnum::values())
            ->reject(fn (string $role): bool => $role === RoleEnum::SystemAdmin->value && ! auth()->user()->isSystemAdmin())
            ->values()
            ->all();
    }
}; ?>

<div class="py-8">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-semibold text-gray-900">{{ __('Utilisateurs') }}</h1>

            @if (auth()->user()->isSystemAdmin())
                <x-primary-button
                    type="button"
                    wire:click="openCreateModal"
                    class="flex items-center gap-1.5"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    {{ __('Nouveau') }}
                </x-primary-button>
            @endif
        </div>

        <x-loading-spinner target="activate,deactivate,updateRole,createUser,openCreateModal" class="mt-4" />

        @if (auth()->user()->isSystemAdmin())
            <x-modal name="create-user-modal" :show="$errors->isNotEmpty()" focusable>
                <form wire:submit="createUser" class="p-6">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                        <div>
                            <h2 class="text-lg font-medium text-gray-900">
                                {{ __('Créer un utilisateur') }}
                            </h2>
                            <p class="mt-1 text-sm text-gray-600">
                                {{ __('Renseignez les informations de l\'utilisateur, son rôle et son cabinet.') }}
                            </p>
                        </div>
                        <button type="button" x-on:click="$dispatch('close')" wire:click="resetCreateForm" class="text-gray-400 hover:text-gray-500">
                            <span class="sr-only">{{ __('Fermer') }}</span>
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="mt-6 space-y-4">
                        <!-- Nom complet -->
                        <div>
                            <x-input-label for="new_nom" :value="__('Nom complet')" />
                            <x-text-input
                                id="new_nom"
                                wire:model="nom"
                                type="text"
                                class="mt-1 block w-full"
                                placeholder="{{ __('ex: Jean Dupont') }}"
                                required
                            />
                            <x-input-error :messages="$errors->get('nom')" class="mt-2" />
                        </div>

                        <!-- Email -->
                        <div>
                            <x-input-label for="new_email" :value="__('Adresse email')" />
                            <x-text-input
                                id="new_email"
                                wire:model="email"
                                type="email"
                                class="mt-1 block w-full"
                                placeholder="{{ __('ex: jean.dupont@example.com') }}"
                                required
                            />
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>

                        <!-- Mot de passe initial -->
                        <div>
                            <x-input-label for="new_password" :value="__('Mot de passe initial')" />
                            <x-text-input
                                id="new_password"
                                wire:model="password"
                                type="password"
                                class="mt-1 block w-full"
                                placeholder="••••••••"
                                required
                            />
                            <p class="mt-1 text-xs text-gray-500">{{ __('Minimum 8 caractères.') }}</p>
                            <x-input-error :messages="$errors->get('password')" class="mt-2" />
                        </div>

                        <!-- Rôle -->
                        <div>
                            <x-input-label for="new_role" :value="__('Rôle')" />
                            <select
                                id="new_role"
                                wire:model.live="role_name"
                                class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                required
                            >
                                <option value="{{ \App\Enums\RoleEnum::Avocat->value }}">{{ \App\Enums\RoleEnum::Avocat->label() }}</option>
                                <option value="{{ \App\Enums\RoleEnum::Comptable->value }}">{{ \App\Enums\RoleEnum::Comptable->label() }}</option>
                                <option value="{{ \App\Enums\RoleEnum::Stagiaire->value }}">{{ \App\Enums\RoleEnum::Stagiaire->label() }}</option>
                                <option value="{{ \App\Enums\RoleEnum::Secretaire->value }}">{{ \App\Enums\RoleEnum::Secretaire->label() }}</option>
                                <option value="{{ \App\Enums\RoleEnum::Admin->value }}">{{ \App\Enums\RoleEnum::Admin->label() }}</option>
                                <option value="{{ \App\Enums\RoleEnum::SystemAdmin->value }}">{{ \App\Enums\RoleEnum::SystemAdmin->label() }}</option>
                            </select>
                            <x-input-error :messages="$errors->get('role_name')" class="mt-2" />
                        </div>

                        <!-- Cabinet -->
                        <div>
                            <x-input-label for="new_cabinet" :value="__('Cabinet')" />
                            <select
                                id="new_cabinet"
                                wire:model="cabinet_id"
                                class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                @if ($role_name !== \App\Enums\RoleEnum::SystemAdmin->value) required @endif
                            >
                                @if ($role_name === \App\Enums\RoleEnum::SystemAdmin->value)
                                    <option value="">{{ __('Aucun cabinet (Administrateur système global)') }}</option>
                                @else
                                    <option value="">{{ __('-- Sélectionner un cabinet --') }}</option>
                                @endif
                                @foreach ($activeCabinets as $cabinet)
                                    <option value="{{ $cabinet->id }}">{{ $cabinet->denomination }}</option>
                                @endforeach
                            </select>
                            @if ($role_name === \App\Enums\RoleEnum::SystemAdmin->value)
                                <p class="mt-1 text-xs text-gray-500">{{ __('Les administrateurs système ont un accès global à l\'ensemble de la plateforme.') }}</p>
                            @endif
                            <x-input-error :messages="$errors->get('cabinet_id')" class="mt-2" />
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-3 border-t border-gray-100 pt-4">
                        <x-secondary-button
                            type="button"
                            x-on:click="$dispatch('close')"
                            wire:click="resetCreateForm"
                        >
                            {{ __('Annuler') }}
                        </x-secondary-button>

                        <x-primary-button wire:loading.attr="disabled">
                            {{ __('Créer l\'utilisateur') }}
                        </x-primary-button>
                    </div>
                </form>
            </x-modal>
        @endif

        <div class="mt-6 overflow-hidden border border-gray-200 bg-white shadow-sm sm:rounded-lg">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-4 py-3">{{ __('Nom') }}</th>
                        <th class="px-4 py-3">{{ __('Cabinet') }}</th>
                        <th class="px-4 py-3">{{ __('Email') }}</th>
                        <th class="px-4 py-3">{{ __('Actif') }}</th>
                        <th class="px-4 py-3">{{ __('Role') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($users as $user)
                        <tr>
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $user->nom }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $user->cabinet?->denomination ?? __('Systeme') }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $user->email }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $user->is_active ? __('Oui') : __('Non') }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <select wire:model="roleUpdates.{{ $user->id }}" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        @foreach ($roles as $role)
                                            <option value="{{ $role->value }}">{{ $role->label() }}</option>
                                        @endforeach
                                    </select>
                                    <button
                                        type="button"
                                        wire:click="updateRole('{{ $user->id }}')"
                                        wire:loading.attr="disabled"
                                        class="text-sm font-medium text-gray-900 hover:text-gray-700"
                                    >{{ __('Enregistrer') }}</button>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-right">
                                @if ($user->is_active)
                                    <button
                                        type="button"
                                        wire:click="deactivate('{{ $user->id }}')"
                                        wire:confirm="{{ __('Voulez-vous vraiment desactiver cet utilisateur ?') }}"
                                        wire:loading.attr="disabled"
                                        class="text-sm font-medium text-amber-700 hover:text-amber-900"
                                    >{{ __('Desactiver') }}</button>
                                @else
                                    <button
                                        type="button"
                                        wire:click="activate('{{ $user->id }}')"
                                        wire:confirm="{{ __('Voulez-vous vraiment activer cet utilisateur ?') }}"
                                        wire:loading.attr="disabled"
                                        class="text-sm font-medium text-green-700 hover:text-green-900"
                                    >{{ __('Activer') }}</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-gray-500">{{ __('Aucun utilisateur pour le moment.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
