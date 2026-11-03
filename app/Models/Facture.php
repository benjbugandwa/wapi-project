<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FactureStatusEnum;
use App\Enums\FactureTypeEnum;
use App\Models\Concerns\BelongsToCabinet;
use App\Models\Concerns\UsesUuid;
use Database\Factories\FactureFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Facture extends Model
{
    /** @use HasFactory<FactureFactory> */
    use BelongsToCabinet, HasFactory, SoftDeletes, UsesUuid;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'cabinet_id',
        'client_id',
        'dossier_id',
        'numero_facture',
        'type_facture',
        'statut',
        'date_emission',
        'date_echeance',
        'montant_ht',
        'taux_tva',
        'montant_tva',
        'montant_ttc',
        'notes_client',
        'created_by',
        'updated_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type_facture' => FactureTypeEnum::class,
            'statut' => FactureStatusEnum::class,
            'date_emission' => 'date',
            'date_echeance' => 'date',
            'montant_ht' => 'decimal:2',
            'taux_tva' => 'decimal:2',
            'montant_tva' => 'decimal:2',
            'montant_ttc' => 'decimal:2',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function dossier(): BelongsTo
    {
        return $this->belongsTo(Dossier::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function lignes(): HasMany
    {
        return $this->hasMany(LigneFacture::class);
    }

    public function paiements(): HasMany
    {
        return $this->hasMany(Paiement::class);
    }

    public function isPaid(): bool
    {
        return $this->statut === FactureStatusEnum::Soldee;
    }

    public function isCancelled(): bool
    {
        return $this->statut === FactureStatusEnum::Annulee;
    }

    public function paidAmount(): string
    {
        return (string) $this->paiements()->sum('montant_paye');
    }

    public function remainingAmount(): string
    {
        return number_format(max(0, (float) $this->montant_ttc - (float) $this->paidAmount()), 2, '.', '');
    }
}
