<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lignes_facture', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('cabinet_id')->constrained('cabinets')->cascadeOnDelete();
            $table->foreignUuid('facture_id')->constrained('factures')->cascadeOnDelete();
            $table->text('description');
            $table->decimal('quantite', 10, 2);
            $table->decimal('prix_unitaire', 12, 2);
            $table->decimal('total_ligne_ht', 12, 2);
            $table->timestamps();

            $table->index('cabinet_id');
            $table->index('facture_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lignes_facture');
    }
};
