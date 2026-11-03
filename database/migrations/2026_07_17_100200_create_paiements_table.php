<?php

declare(strict_types=1);

use App\Enums\MethodePaiementEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paiements', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('cabinet_id')->constrained('cabinets')->cascadeOnDelete();
            $table->foreignUuid('facture_id')->constrained('factures')->restrictOnDelete();
            $table->decimal('montant_paye', 12, 2);
            $table->date('date_paiement');
            $table->string('methode_paiement', 100)->default(MethodePaiementEnum::Virement->value);
            $table->string('reference_transaction')->nullable();
            $table->foreignUuid('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('cabinet_id');
            $table->index('facture_id');
            $table->index('date_paiement');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paiements');
    }
};
