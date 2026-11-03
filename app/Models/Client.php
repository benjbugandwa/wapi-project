<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ClientTypeEnum;
use App\Models\Concerns\BelongsToCabinet;
use App\Models\Concerns\UsesUuid;
use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    /** @use HasFactory<ClientFactory> */
    use BelongsToCabinet, HasFactory, SoftDeletes, UsesUuid;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'cabinet_id',
        'type_client',
        'nom',
        'prenom',
        'email',
        'telephone_principal',
        'adresse_rue',
        'ville',
        'numero_identification',
        'representant_legal',
        'notes_internes',
        'created_by',
        'updated_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type_client' => ClientTypeEnum::class,
        ];
    }

    /**
     * @return BelongsTo<User, Client>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, Client>
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * @return HasMany<Document>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    /**
     * @return HasMany<Dossier>
     */
    public function dossiers(): HasMany
    {
        return $this->hasMany(Dossier::class);
    }

    /**
     * @return HasMany<Facture>
     */
    public function factures(): HasMany
    {
        return $this->hasMany(Facture::class);
    }
}
