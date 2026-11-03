<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cabinet_invitations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('cabinet_id')->constrained('cabinets')->cascadeOnDelete();
            $table->string('email');
            $table->string('role_name', 50);
            $table->string('token');
            $table->foreignUuid('invited_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->unique(['cabinet_id', 'email']);
            $table->index('role_name');
            $table->index('token');
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cabinet_invitations');
    }
};
