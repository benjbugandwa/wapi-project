<?php

declare(strict_types=1);

use App\Enums\EvenementStatusEnum;
use App\Enums\EvenementTypeEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evenements', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('cabinet_id')->constrained('cabinets')->cascadeOnDelete();
            $table->foreignUuid('dossier_id')->nullable()->constrained('dossiers')->nullOnDelete();
            $table->string('titre');
            $table->string('type_evenement', 50)->default(EvenementTypeEnum::RendezVous->value);
            $table->string('statut_evenement', 50)->default(EvenementStatusEnum::Planifie->value);
            $table->timestamp('date_debut');
            $table->timestamp('date_fin');
            $table->string('lieu')->nullable();
            $table->text('description')->nullable();
            $table->foreignUuid('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignUuid('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('cabinet_id');
            $table->index('dossier_id');
            $table->index(['cabinet_id', 'date_debut']);
            $table->index('type_evenement');
            $table->index(['cabinet_id', 'statut_evenement']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evenements');
    }
};
