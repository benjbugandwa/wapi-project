<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\SaveNotificationPreferencesData;
use App\Enums\NotificationChannelEnum;
use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class NotificationPreferenceService
{
    public function save(User $user, SaveNotificationPreferencesData $data): void
    {
        $this->ensureUserHasCabinet($user);

        DB::transaction(function () use ($user, $data): void {
            foreach (NotificationChannelEnum::cases() as $channel) {
                NotificationPreference::query()->updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'canal_notification' => $channel->value,
                    ],
                    [
                        'cabinet_id' => $user->cabinet_id,
                        'is_enabled' => $data->enabledChannels[$channel->value] ?? $this->defaultEnabled($channel),
                        'destination' => $data->destinations[$channel->value] ?? $this->defaultDestination($user, $channel),
                    ],
                );
            }
        });
    }

    public function enabled(User $user, NotificationChannelEnum $channel): bool
    {
        $preference = $user->notificationPreferences
            ->first(fn (NotificationPreference $preference): bool => $preference->canal_notification === $channel);

        return $preference?->is_enabled ?? $this->defaultEnabled($channel);
    }

    public function destination(User $user, NotificationChannelEnum $channel): ?string
    {
        $preference = $user->notificationPreferences
            ->first(fn (NotificationPreference $preference): bool => $preference->canal_notification === $channel);

        return $preference?->destination ?? $this->defaultDestination($user, $channel);
    }

    private function defaultEnabled(NotificationChannelEnum $channel): bool
    {
        return in_array($channel, [NotificationChannelEnum::Email, NotificationChannelEnum::InApp], true);
    }

    private function defaultDestination(User $user, NotificationChannelEnum $channel): ?string
    {
        return match ($channel) {
            NotificationChannelEnum::Email => $user->email,
            NotificationChannelEnum::Sms, NotificationChannelEnum::WhatsApp => $user->phone_number,
            default => null,
        };
    }

    private function ensureUserHasCabinet(User $user): void
    {
        if ($user->cabinet_id !== null) {
            return;
        }

        throw ValidationException::withMessages([
            'cabinet' => __('Les preferences de notification doivent etre liees a un cabinet.'),
        ]);
    }
}
