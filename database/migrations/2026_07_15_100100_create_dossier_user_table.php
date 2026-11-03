<?php

declare(strict_types=1);

use App\Enums\DossierUserRoleEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dossier_user', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('cabinet_id')->constrained('cabinets')->cascadeOnDelete();
            $table->foreignUuid('dossier_id')->constrained('dossiers')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role_dossier', 50)->default(DossierUserRoleEnum::Collaborateur->value);
            $table->timestamps();

            $table->unique(['dossier_id', 'user_id']);
            $table->index('cabinet_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dossier_user');
    }
};
