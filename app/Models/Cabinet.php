<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CabinetStatus;
use App\Models\Concerns\UsesUuid;
use Database\Factories\CabinetFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cabinet extends Model
{
    /** @use HasFactory<CabinetFactory> */
    use HasFactory, SoftDeletes, UsesUuid;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'denomination',
        'adresse_cabinet',
        'description',
        'nom_repondant',
        'telephone_repondant',
        'email_repondant',
        'statut_cabinet',
        'logo_cabinet',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'statut_cabinet' => CabinetStatus::class,
        ];
    }

    /**
     * @return HasMany<User>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * @return HasMany<CabinetInvitation>
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(CabinetInvitation::class);
    }

    /**
     * @return HasMany<AuditLog>
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    /**
     * @return HasMany<Client>
     */
    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
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

    /**
     * @return HasMany<Paiement>
     */
    public function paiements(): HasMany
    {
        return $this->hasMany(Paiement::class);
    }

    public function isActive(): bool
    {
        return $this->statut_cabinet === CabinetStatus::Active;
    }
}
