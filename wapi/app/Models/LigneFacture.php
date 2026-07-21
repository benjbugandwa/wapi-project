<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LigneFacture extends Model
{
    use UsesUuid;

    protected $table = 'lignes_facture';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'cabinet_id',
        'facture_id',
        'description',
        'quantite',
        'prix_unitaire',
        'total_ligne_ht',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantite' => 'decimal:2',
            'prix_unitaire' => 'decimal:2',
            'total_ligne_ht' => 'decimal:2',
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
}
