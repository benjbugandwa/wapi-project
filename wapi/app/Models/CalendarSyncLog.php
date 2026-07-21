<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CalendarProviderEnum;
use App\Enums\CalendarSyncStatusEnum;
use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalendarSyncLog extends Model
{
    use UsesUuid;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'cabinet_id',
        'calendar_integration_id',
        'evenement_id',
        'provider',
        'external_event_id',
        'sync_status',
        'error_message',
        'payload',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'provider' => CalendarProviderEnum::class,
            'sync_status' => CalendarSyncStatusEnum::class,
            'payload' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Cabinet, CalendarSyncLog>
     */
    public function cabinet(): BelongsTo
    {
        return $this->belongsTo(Cabinet::class);
    }

    /**
     * @return BelongsTo<CalendarIntegration, CalendarSyncLog>
     */
    public function calendarIntegration(): BelongsTo
    {
        return $this->belongsTo(CalendarIntegration::class);
    }

    /**
     * @return BelongsTo<Evenement, CalendarSyncLog>
     */
    public function evenement(): BelongsTo
    {
        return $this->belongsTo(Evenement::class);
    }
}
