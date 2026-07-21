<?php

declare(strict_types=1);

use App\Enums\DomaineDroitEnum;
use App\Enums\DossierStatusEnum;
use App\Enums\TypeFacturationEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dossiers', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('cabinet_id')->constrained('cabinets')->cascadeOnDelete();
            $table->foreignUuid('client_id')->constrained('clients')->restrictOnDelete();
            $table->string('numero_reference', 50);
            $table->string('titre_dossier');
            $table->text('description')->nullable();
            $table->string('domaine_droit', 100)->default(DomaineDroitEnum::Autre->value);
            $table->string('statut_dossier', 50)->default(DossierStatusEnum::Ouvert->value);
            $table->date('date_ouverture');
            $table->date('date_cloture')->nullable();
            $table->string('juridiction', 150)->nullable();
            $table->string('type_facturation', 50)->default(TypeFacturationEnum::Forfait->value);
            $table->foreignUuid('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['cabinet_id', 'numero_reference']);
            $table->index('cabinet_id');
            $table->index('client_id');
            $table->index(['cabinet_id', 'statut_dossier']);
            $table->index('date_ouverture');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dossiers');
    }
};
