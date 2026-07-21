<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EvenementStatusEnum;
use App\Enums\EvenementTypeEnum;
use App\Models\Concerns\UsesUuid;
use Database\Factories\EvenementFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Evenement extends Model
{
    /** @use HasFactory<EvenementFactory> */
    use HasFactory, SoftDeletes, UsesUuid;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'cabinet_id',
        'dossier_id',
        'titre',
        'type_evenement',
        'statut_evenement',
        'date_debut',
        'date_fin',
        'lieu',
        'description',
        'created_by',
        'updated_by',
        'cancelled_at',
        'cancelled_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type_evenement' => EvenementTypeEnum::class,
            'statut_evenement' => EvenementStatusEnum::class,
            'date_debut' => 'datetime',
            'date_fin' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Cabinet, Evenement>
     */
    public function cabinet(): BelongsTo
    {
        return $this->belongsTo(Cabinet::class);
    }

    /**
     * @return BelongsTo<Dossier, Evenement>
     */
    public function dossier(): BelongsTo
    {
        return $this->belongsTo(Dossier::class);
    }

    /**
     * @return BelongsTo<User, Evenement>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, Evenement>
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * @return BelongsTo<User, Evenement>
     */
    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    /**
     * @return BelongsToMany<User>
     */
    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'agenda_participants')
            ->withPivot(['id', 'cabinet_id'])
            ->withTimestamps();
    }

    /**
     * @return HasMany<Rappel>
     */
    public function rappels(): HasMany
    {
        return $this->hasMany(Rappel::class);
    }

    /**
     * @return HasMany<CalendarSyncLog>
     */
    public function calendarSyncLogs(): HasMany
    {
        return $this->hasMany(CalendarSyncLog::class);
    }

    public function isCancelled(): bool
    {
        return $this->statut_evenement === EvenementStatusEnum::Annule;
    }
}
