<?php

declare(strict_types=1);

use App\Enums\NotificationProviderEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_webhook_events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('cabinet_id')->nullable()->constrained('cabinets')->nullOnDelete();
            $table->string('provider', 50)->default(NotificationProviderEnum::Twilio->value);
            $table->string('channel', 30);
            $table->string('external_message_id')->nullable();
            $table->string('from', 100)->nullable();
            $table->string('to', 100)->nullable();
            $table->text('body')->nullable();
            $table->json('payload');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index('cabinet_id');
            $table->index('provider');
            $table->index('external_message_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_webhook_events');
    }
};
