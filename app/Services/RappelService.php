<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\SaveRappelsData;
use App\Enums\NotificationChannelEnum;
use App\Jobs\SendRappelNotificationJob;
use App\Models\Evenement;
use App\Models\Rappel;
use Illuminate\Support\Facades\DB;

class RappelService
{
    public function saveForEvenement(Evenement $evenement, SaveRappelsData $data): void
    {
        $evenement->loadMissing('participants');

        DB::transaction(function () use ($evenement, $data): void {
            $evenement->rappels()->delete();

            foreach ($evenement->participants as $participant) {
                foreach ($data->channels as $channelValue) {
                    $channel = NotificationChannelEnum::from($channelValue);
                    $dateDeclenchement = $evenement->date_debut->copy()->subMinutes($data->delaiMinutes);

                    $rappel = Rappel::query()->create([
                        'cabinet_id' => $evenement->cabinet_id,
                        'evenement_id' => $evenement->id,
                        'user_id' => $participant->id,
                        'canal_notification' => $channel,
                        'delai_minutes' => $data->delaiMinutes,
                        'date_declenchement' => $dateDeclenchement,
                    ]);

                    SendRappelNotificationJob::dispatch($rappel)
                        ->delay($dateDeclenchement->isFuture() ? $dateDeclenchement : now());
                }
            }
        });
    }
}
