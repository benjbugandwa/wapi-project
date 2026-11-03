<?php

declare(strict_types=1);

use App\Enums\NotificationChannelEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_preferences', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('cabinet_id')->constrained('cabinets')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('canal_notification', 30)->default(NotificationChannelEnum::Email->value);
            $table->boolean('is_enabled')->default(true);
            $table->string('destination')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'canal_notification']);
            $table->index('cabinet_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }
};
