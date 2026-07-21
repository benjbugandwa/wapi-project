<?php

use App\DTO\UpdateUserRoleData;
use App\Enums\RoleEnum;
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

    public function mount(): void
    {
        Gate::authorize('viewAny', User::class);

        $this->roleUpdates = $this->usersQuery()
            ->get()
            ->mapWithKeys(fn (User $user): array => [$user->id => $user->roles->first()?->name ?? RoleEnum::Avocat->value])
            ->all();
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
        <h1 class="text-xl font-semibold text-gray-900">{{ __('Utilisateurs') }}</h1>

        <x-loading-spinner target="activate,deactivate,updateRole" class="mt-4" />

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
