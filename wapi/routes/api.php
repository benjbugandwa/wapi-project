<?php

declare(strict_types=1);

use App\Http\Controllers\TwilioWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('webhooks/twilio/messages', TwilioWebhookController::class)
    ->name('webhooks.twilio.messages');
