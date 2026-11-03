<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RoleEnum;
use App\Models\Concerns\UsesUuid;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, SoftDeletes, UsesUuid;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'cabinet_id',
        'nom',
        'email',
        'password',
        'phone_number',
        'profile_photo_path',
        'is_active',
        'last_login_at',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Compatibility alias for Laravel/Breeze UI conventions.
     *
     * @return Attribute<string, string>
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->nom,
            set: fn (string $value): array => ['nom' => $value],
        );
    }

    /**
     * @return BelongsTo<Cabinet, User>
     */
    public function cabinet(): BelongsTo
    {
        return $this->belongsTo(Cabinet::class);
    }

    /**
     * @return HasMany<CabinetInvitation>
     */
    public function sentCabinetInvitations(): HasMany
    {
        return $this->hasMany(CabinetInvitation::class, 'invited_by');
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
    public function createdClients(): HasMany
    {
        return $this->hasMany(Client::class, 'created_by');
    }

    /**
     * @return HasMany<Document>
     */
    public function uploadedDocuments(): HasMany
    {
        return $this->hasMany(Document::class, 'uploaded_by');
    }

    /**
     * @return HasMany<Dossier>
     */
    public function createdDossiers(): HasMany
    {
        return $this->hasMany(Dossier::class, 'created_by');
    }

    /**
     * @return BelongsToMany<Dossier>
     */
    public function assignedDossiers(): BelongsToMany
    {
        return $this->belongsToMany(Dossier::class)
            ->withPivot(['id', 'cabinet_id', 'role_dossier'])
            ->withTimestamps();
    }

    /**
     * @return HasMany<Evenement>
     */
    public function createdEvenements(): HasMany
    {
        return $this->hasMany(Evenement::class, 'created_by');
    }

    /**
     * @return BelongsToMany<Evenement>
     */
    public function agendaEvenements(): BelongsToMany
    {
        return $this->belongsToMany(Evenement::class, 'agenda_participants')
            ->withPivot(['id', 'cabinet_id'])
            ->withTimestamps();
    }

    /**
     * @return HasMany<NotificationPreference>
     */
    public function notificationPreferences(): HasMany
    {
        return $this->hasMany(NotificationPreference::class);
    }

    /**
     * @return HasMany<CalendarIntegration>
     */
    public function calendarIntegrations(): HasMany
    {
        return $this->hasMany(CalendarIntegration::class);
    }

    /**
     * @return HasMany<Facture>
     */
    public function createdFactures(): HasMany
    {
        return $this->hasMany(Facture::class, 'created_by');
    }

    /**
     * @return HasMany<Paiement>
     */
    public function createdPaiements(): HasMany
    {
        return $this->hasMany(Paiement::class, 'created_by');
    }

    public function isSystemAdmin(): bool
    {
        return $this->hasRole(RoleEnum::SystemAdmin->value);
    }

    public function belongsToActiveCabinet(): bool
    {
        return $this->cabinet !== null && $this->cabinet->isActive();
    }

    public function canAccessCabinet(Cabinet|string|null $cabinet): bool
    {
        if ($this->isSystemAdmin()) {
            return true;
        }

        $cabinetId = $cabinet instanceof Cabinet ? $cabinet->id : $cabinet;

        return $cabinetId !== null && $this->cabinet_id === $cabinetId;
    }

    public function primaryRole(): RoleEnum
    {
        if ($this->hasRole(RoleEnum::SystemAdmin->value)) {
            return RoleEnum::SystemAdmin;
        }

        if ($this->hasRole(RoleEnum::Admin->value)) {
            return RoleEnum::Admin;
        }

        if ($this->hasRole(RoleEnum::Avocat->value)) {
            return RoleEnum::Avocat;
        }

        if ($this->hasRole(RoleEnum::Comptable->value)) {
            return RoleEnum::Comptable;
        }

        if ($this->hasRole(RoleEnum::Secretaire->value)) {
            return RoleEnum::Secretaire;
        }

        if ($this->hasRole(RoleEnum::Stagiaire->value)) {
            return RoleEnum::Stagiaire;
        }

        return RoleEnum::Avocat;
    }
}
