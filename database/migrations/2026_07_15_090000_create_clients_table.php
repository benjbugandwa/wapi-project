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
        Schema::create('clients', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('cabinet_id')->constrained('cabinets')->cascadeOnDelete();
            $table->string('type_client', 30)->default(ClientTypeEnum::PersonnePhysique->value);
            $table->string('nom', 150);
            $table->string('prenom', 100)->nullable();
            $table->string('email')->nullable();
            $table->string('telephone_principal', 50)->nullable();
            $table->string('adresse_rue')->nullable();
            $table->string('ville', 100)->nullable();
            $table->string('numero_identification', 100)->nullable();
            $table->string('representant_legal', 150)->nullable();
            $table->text('notes_internes')->nullable();
            $table->foreignUuid('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('cabinet_id');
            $table->index('nom');
            $table->index('email');
            $table->index(['cabinet_id', 'type_client']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
