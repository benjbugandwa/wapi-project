<?php

use App\Jobs\SendRappelNotificationJob;
use App\Models\Rappel;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function (): void {
    Rappel::query()
        ->where('est_envoye', false)
        ->where('date_declenchement', '<=', now())
        ->orderBy('date_declenchement')
        ->limit(250)
        ->get()
        ->each(fn (Rappel $rappel) => SendRappelNotificationJob::dispatch($rappel));
})
    ->name('dispatch-due-reminders')
    ->everyMinute()
    ->withoutOverlapping();

Schedule::command('queue:prune-failed --hours=168')
    ->dailyAt('03:15')
    ->withoutOverlapping();
