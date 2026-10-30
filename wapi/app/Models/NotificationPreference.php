<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\NotificationChannelEnum;
use App\Models\Concerns\BelongsToCabinet;
use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    use BelongsToCabinet, UsesUuid;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'cabinet_id',
        'user_id',
        'canal_notification',
        'is_enabled',
        'destination',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'canal_notification' => NotificationChannelEnum::class,
            'is_enabled' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, NotificationPreference>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
