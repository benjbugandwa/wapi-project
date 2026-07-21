<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('cabinet_id')->constrained('cabinets')->cascadeOnDelete();
            $table->uuid('dossier_id')->nullable();
            $table->foreignUuid('client_id')->nullable()->constrained('clients')->cascadeOnDelete();
            $table->string('nom_original');
            $table->string('nom_systeme');
            $table->string('chemin_acces', 500);
            $table->string('extension', 20);
            $table->string('type_mime', 150);
            $table->unsignedBigInteger('taille_octets');
            $table->string('categorie', 100);
            $table->boolean('est_confidentiel')->default(true);
            $table->foreignUuid('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('cabinet_id');
            $table->index('dossier_id');
            $table->index('client_id');
            $table->index(['cabinet_id', 'categorie']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
