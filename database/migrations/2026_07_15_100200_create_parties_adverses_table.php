<?php

declare(strict_types=1);

use App\Enums\ClientTypeEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parties_adverses', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('cabinet_id')->constrained('cabinets')->cascadeOnDelete();
            $table->foreignUuid('dossier_id')->constrained('dossiers')->cascadeOnDelete();
            $table->string('type_partie', 30)->default(ClientTypeEnum::PersonnePhysique->value);
            $table->string('nom', 150);
            $table->string('prenom', 100)->nullable();
            $table->string('email')->nullable();
            $table->string('telephone', 50)->nullable();
            $table->string('adresse')->nullable();
            $table->string('avocat_nom', 150)->nullable();
            $table->string('avocat_email')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('cabinet_id');
            $table->index('dossier_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parties_adverses');
    }
};
