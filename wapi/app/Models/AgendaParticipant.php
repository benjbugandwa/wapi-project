<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgendaParticipant extends Model
{
    use UsesUuid;

    protected $table = 'agenda_participants';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'cabinet_id',
        'evenement_id',
        'user_id',
    ];

    /**
     * @return BelongsTo<Cabinet, AgendaParticipant>
     */
    public function cabinet(): BelongsTo
    {
        return $this->belongsTo(Cabinet::class);
    }

    /**
     * @return BelongsTo<Evenement, AgendaParticipant>
     */
    public function evenement(): BelongsTo
    {
        return $this->belongsTo(Evenement::class);
    }

    /**
     * @return BelongsTo<User, AgendaParticipant>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
