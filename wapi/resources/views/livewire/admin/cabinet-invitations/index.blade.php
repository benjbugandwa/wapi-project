<?php

use App\DTO\CreateCabinetInvitationData;
use App\Enums\RoleEnum;
use App\Models\Cabinet;
use App\Models\CabinetInvitation;
use App\Services\CabinetInvitationService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public ?string $cabinet_id = null;
    public string $email = '';
    public string $role_name = '';
    public ?string $lastInvitationLink = null;

    public function mount(): void
    {
        Gate::authorize('viewAny', CabinetInvitation::class);

        $user = auth()->user();
        $this->cabinet_id = $user->isSystemAdmin() ? null : $user->cabinet_id;
        $this->role_name = RoleEnum::Avocat->value;
    }

    public function invite(CabinetInvitationService $invitationService): void
    {
        Gate::authorize('create', CabinetInvitation::class);

        $allowedRoles = collect(RoleEnum::values())
            ->reject(fn (string $role): bool => $role === RoleEnum::SystemAdmin->value && ! auth()->user()->isSystemAdmin())
            ->values()
            ->all();

        $validated = $this->validate([
            'cabinet_id' => ['required', 'uuid', 'exists:cabinets,id'],
            'email' => ['required', 'email', 'max:255'],
            'role_name' => ['required', 'string', Rule::in($allowedRoles)],
        ]);

        $cabinet = Cabinet::query()->findOrFail($validated['cabinet_id']);

        abort_unless(auth()->user()->canAccessCabinet($cabinet), 403);

        $result = $invitationService->create(new CreateCabinetInvitationData(
            cabinet: $cabinet,
            email: $validated['email'],
            roleName: $validated['role_name'],
            inviter: auth()->user(),
            expiresAt: CarbonImmutable::now()->addDays(7),
        ));

        $this->lastInvitationLink = route('cabinet-invitations.accept', $result['token']);
        $this->reset('email');
        $this->dispatch('close-modal', 'cabinet-invitation-form');

        if ($result['mail_sent']) {
            notify()->success(__('Invitation envoyée avec succès.'), __('Succès'));
        } else {
            notify()->warning(
                __("L'invitation a été enregistrée, mais l'envoi de l'email a échoué. Vous pouvez copier le lien d'invitation ci-dessous."),
                __('Attention')
            );
        }
    }

    public function with(): array
    {
        $user = auth()->user();

        return [
            'cabinets' => $user->isSystemAdmin()
                ? Cabinet::query()->orderBy('denomination')->get()
                : Cabinet::query()->whereKey($user->cabinet_id)->get(),
            'roles' => collect(RoleEnum::cases())
                ->reject(fn (RoleEnum $role): bool => $role === RoleEnum::SystemAdmin && ! $user->isSystemAdmin())
                ->all(),
            'invitations' => CabinetInvitation::query()
                ->with(['cabinet', 'inviter'])
                ->when(! $user->isSystemAdmin(), fn ($query) => $query->where('cabinet_id', $user->cabinet_id))
                ->latest()
                ->get(),
        ];
    }
}; ?>

<div class="py-8">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <h1 class="text-xl font-semibold text-gray-900">{{ __('Invitations cabinet') }}</h1>

        @if ($lastInvitationLink)
            <div class="mt-4 rounded-md border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <p class="font-semibold">{{ __('Lien d\'invitation généré :') }}</p>
                        <p class="mt-1 select-all font-mono text-xs text-amber-800 break-all">{{ $lastInvitationLink }}</p>
                    </div>
                    <button
                        type="button"
                        x-data=""
                        x-on:click="navigator.clipboard.writeText('{{ $lastInvitationLink }}'); alert('{{ __('Lien copié dans le presse-papier !') }}')"
                        class="shrink-0 rounded bg-amber-200 px-3 py-1.5 text-xs font-medium text-amber-900 hover:bg-amber-300 self-start sm:self-center"
                    >
                        {{ __('Copier le lien') }}
                    </button>
                </div>
            </div>
        @endif

        <x-loading-spinner target="invite" class="mt-4" />

        <div class="mt-6 flex justify-end">
            <button
                type="button"
                x-data=""
                x-on:click.prevent="$dispatch('open-modal', 'cabinet-invitation-form')"
                class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800"
            >{{ __('Nouvelle invitation') }}</button>
        </div>

        <x-modal name="cabinet-invitation-form" maxWidth="2xl" focusable>
            <form wire:submit="invite" class="space-y-4 p-6">
                <h2 class="text-lg font-medium text-gray-900">{{ __('Inviter un utilisateur') }}</h2>

                <div>
                    <x-input-label for="cabinet_id" :value="__('Cabinet')" />
                    <select id="cabinet_id" wire:model="cabinet_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" @disabled(! auth()->user()->isSystemAdmin())>
                        <option value="">{{ __('Selectionner') }}</option>
                        @foreach ($cabinets as $cabinet)
                            <option value="{{ $cabinet->id }}">{{ $cabinet->denomination }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('cabinet_id')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="email" :value="__('Email')" />
                    <x-text-input id="email" wire:model="email" type="email" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="role_name" :value="__('Role')" />
                    <select id="role_name" wire:model="role_name" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @foreach ($roles as $role)
                            <option value="{{ $role->value }}">{{ $role->label() }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('role_name')" class="mt-2" />
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-gray-100 pt-4">
                    <x-loading-spinner target="invite" />
                    <x-secondary-button type="button" x-on:click="$dispatch('close')">{{ __('Annuler') }}</x-secondary-button>
                    <x-primary-button wire:loading.attr="disabled" wire:target="invite">{{ __('Inviter') }}</x-primary-button>
                </div>
            </form>
        </x-modal>

        <div class="mt-6 overflow-hidden border border-gray-200 bg-white shadow-sm sm:rounded-lg">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-4 py-3">{{ __('Email') }}</th>
                        <th class="px-4 py-3">{{ __('Cabinet') }}</th>
                        <th class="px-4 py-3">{{ __('Role') }}</th>
                        <th class="px-4 py-3">{{ __('Expiration') }}</th>
                        <th class="px-4 py-3">{{ __('Statut') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($invitations as $invitation)
                        <tr>
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $invitation->email }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $invitation->cabinet->denomination }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $invitation->role_name }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $invitation->expires_at->format('Y-m-d') }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $invitation->accepted_at ? __('Acceptee') : __('En attente') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-gray-500">{{ __('Aucune invitation pour le moment.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
