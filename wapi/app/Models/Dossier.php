<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DomaineDroitEnum;
use App\Enums\DossierStatusEnum;
use App\Enums\TypeFacturationEnum;
use App\Models\Concerns\UsesUuid;
use Carbon\CarbonInterface;
use Database\Factories\DossierFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Dossier extends Model
{
    /** @use HasFactory<DossierFactory> */
    use HasFactory, SoftDeletes, UsesUuid;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'cabinet_id',
        'client_id',
        'numero_reference',
        'titre_dossier',
        'description',
        'domaine_droit',
        'statut_dossier',
        'date_ouverture',
        'date_cloture',
        'juridiction',
        'type_facturation',
        'created_by',
        'updated_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'domaine_droit' => DomaineDroitEnum::class,
            'statut_dossier' => DossierStatusEnum::class,
            'type_facturation' => TypeFacturationEnum::class,
            'date_ouverture' => 'date',
            'date_cloture' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Cabinet, Dossier>
     */
    public function cabinet(): BelongsTo
    {
        return $this->belongsTo(Cabinet::class);
    }

    /**
     * @return BelongsTo<Client, Dossier>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * @return BelongsTo<User, Dossier>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, Dossier>
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * @return BelongsToMany<User>
     */
    public function assignedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['id', 'cabinet_id', 'role_dossier'])
            ->withTimestamps();
    }

    /**
     * @return HasMany<PartieAdverse>
     */
    public function partiesAdverses(): HasMany
    {
        return $this->hasMany(PartieAdverse::class);
    }

    /**
     * @return HasMany<Document>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    /**
     * @return HasMany<Evenement>
     */
    public function evenements(): HasMany
    {
        return $this->hasMany(Evenement::class);
    }

    /**
     * @return HasMany<Facture>
     */
    public function factures(): HasMany
    {
        return $this->hasMany(Facture::class);
    }

    public function isClosed(): bool
    {
        return $this->statut_dossier === DossierStatusEnum::Clos;
    }

    public function isArchived(): bool
    {
        return $this->statut_dossier === DossierStatusEnum::Archive;
    }

    public function ageInDays(): int
    {
        /** @var CarbonInterface $openingDate */
        $openingDate = $this->date_ouverture;

        return (int) $openingDate->diffInDays(now());
    }
}
