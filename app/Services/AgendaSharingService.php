<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Evenement;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class AgendaSharingService
{
    /**
     * @param  array<int, string>  $participantIds
     */
    public function sync(Evenement $evenement, array $participantIds): void
    {
        $ids = collect($participantIds)
            ->push($evenement->created_by)
            ->filter()
            ->unique()
            ->values();

        $users = User::query()
            ->whereIn('id', $ids)
            ->where('cabinet_id', $evenement->cabinet_id)
            ->get();

        if ($users->count() !== $ids->count()) {
            throw ValidationException::withMessages([
                'participant_ids' => __('Tous les participants doivent appartenir au cabinet de l evenement.'),
            ]);
        }

        $evenement->participants()->sync(
            $users->mapWithKeys(fn (User $user): array => [
                $user->id => ['id' => (string) str()->uuid(), 'cabinet_id' => $evenement->cabinet_id],
            ])->all(),
        );
    }
}
