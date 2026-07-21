<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\NotificationChannelEnum;
use App\Models\Concerns\UsesUuid;
use Database\Factories\RappelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rappel extends Model
{
    /** @use HasFactory<RappelFactory> */
    use HasFactory, UsesUuid;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'cabinet_id',
        'evenement_id',
        'user_id',
        'canal_notification',
        'delai_minutes',
        'date_declenchement',
        'est_envoye',
        'sent_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'canal_notification' => NotificationChannelEnum::class,
            'date_declenchement' => 'datetime',
            'est_envoye' => 'boolean',
            'sent_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Cabinet, Rappel>
     */
    public function cabinet(): BelongsTo
    {
        return $this->belongsTo(Cabinet::class);
    }

    /**
     * @return BelongsTo<Evenement, Rappel>
     */
    public function evenement(): BelongsTo
    {
        return $this->belongsTo(Evenement::class)->withTrashed();
    }

    /**
     * @return BelongsTo<User, Rappel>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
