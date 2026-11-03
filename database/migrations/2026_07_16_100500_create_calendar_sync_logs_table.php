<?php

declare(strict_types=1);

use App\Enums\CalendarProviderEnum;
use App\Enums\CalendarSyncStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_sync_logs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('cabinet_id')->constrained('cabinets')->cascadeOnDelete();
            $table->foreignUuid('calendar_integration_id')->constrained('calendar_integrations')->cascadeOnDelete();
            $table->foreignUuid('evenement_id')->constrained('evenements')->cascadeOnDelete();
            $table->string('provider', 50)->default(CalendarProviderEnum::Google->value);
            $table->string('external_event_id')->nullable();
            $table->string('sync_status', 50)->default(CalendarSyncStatusEnum::Pending->value);
            $table->text('error_message')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index('cabinet_id');
            $table->index('calendar_integration_id');
            $table->index('evenement_id');
            $table->index(['provider', 'sync_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_sync_logs');
    }
};
