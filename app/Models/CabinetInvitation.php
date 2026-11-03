<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToCabinet;
use App\Models\Concerns\UsesUuid;
use Database\Factories\CabinetInvitationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\Notifiable;

class CabinetInvitation extends Model
{
    /** @use HasFactory<CabinetInvitationFactory> */
    use BelongsToCabinet, HasFactory, Notifiable, UsesUuid;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'cabinet_id',
        'email',
        'role_name',
        'token',
        'invited_by',
        'accepted_at',
        'expires_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'accepted_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, CabinetInvitation>
     */
    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function routeNotificationForMail(): string
    {
        return $this->email;
    }
}
