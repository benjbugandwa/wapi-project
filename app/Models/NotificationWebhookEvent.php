<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\NotificationChannelEnum;
use App\Enums\NotificationProviderEnum;
use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationWebhookEvent extends Model
{
    use UsesUuid;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'cabinet_id',
        'provider',
        'channel',
        'external_message_id',
        'from',
        'to',
        'body',
        'payload',
        'processed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'provider' => NotificationProviderEnum::class,
            'channel' => NotificationChannelEnum::class,
            'payload' => 'array',
            'processed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Cabinet, NotificationWebhookEvent>
     */
    public function cabinet(): BelongsTo
    {
        return $this->belongsTo(Cabinet::class);
    }
}
