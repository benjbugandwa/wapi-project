<?php

declare(strict_types=1);

use App\Enums\FactureStatusEnum;
use App\Enums\FactureTypeEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('factures', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('cabinet_id')->constrained('cabinets')->cascadeOnDelete();
            $table->foreignUuid('client_id')->constrained('clients')->restrictOnDelete();
            $table->foreignUuid('dossier_id')->nullable()->constrained('dossiers')->nullOnDelete();
            $table->string('numero_facture', 50);
            $table->string('type_facture', 50)->default(FactureTypeEnum::Definitive->value);
            $table->string('statut', 50)->default(FactureStatusEnum::Brouillon->value);
            $table->date('date_emission');
            $table->date('date_echeance');
            $table->decimal('montant_ht', 12, 2)->default(0);
            $table->decimal('taux_tva', 5, 2)->default(0);
            $table->decimal('montant_tva', 12, 2)->default(0);
            $table->decimal('montant_ttc', 12, 2)->default(0);
            $table->text('notes_client')->nullable();
            $table->foreignUuid('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['cabinet_id', 'numero_facture']);
            $table->index('cabinet_id');
            $table->index('client_id');
            $table->index('dossier_id');
            $table->index(['cabinet_id', 'statut']);
            $table->index('date_echeance');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('factures');
    }
};
