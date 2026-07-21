<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MethodePaiementEnum;
use App\Models\Concerns\UsesUuid;
use Database\Factories\PaiementFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Paiement extends Model
{
    /** @use HasFactory<PaiementFactory> */
    use HasFactory, SoftDeletes, UsesUuid;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'cabinet_id',
        'facture_id',
        'montant_paye',
        'date_paiement',
        'methode_paiement',
        'reference_transaction',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'montant_paye' => 'decimal:2',
            'date_paiement' => 'date',
            'methode_paiement' => MethodePaiementEnum::class,
        ];
    }

    public function cabinet(): BelongsTo
    {
        return $this->belongsTo(Cabinet::class);
    }

    public function facture(): BelongsTo
    {
        return $this->belongsTo(Facture::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
