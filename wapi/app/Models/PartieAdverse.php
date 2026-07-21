<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ClientTypeEnum;
use App\Models\Concerns\UsesUuid;
use Database\Factories\PartieAdverseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PartieAdverse extends Model
{
    /** @use HasFactory<PartieAdverseFactory> */
    use HasFactory, SoftDeletes, UsesUuid;

    protected $table = 'parties_adverses';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'cabinet_id',
        'dossier_id',
        'type_partie',
        'nom',
        'prenom',
        'email',
        'telephone',
        'adresse',
        'avocat_nom',
        'avocat_email',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type_partie' => ClientTypeEnum::class,
        ];
    }

    /**
     * @return BelongsTo<Cabinet, PartieAdverse>
     */
    public function cabinet(): BelongsTo
    {
        return $this->belongsTo(Cabinet::class);
    }

    /**
     * @return BelongsTo<Dossier, PartieAdverse>
     */
    public function dossier(): BelongsTo
    {
        return $this->belongsTo(Dossier::class);
    }
}
