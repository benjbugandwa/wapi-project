<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Cabinet;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AuditLogService
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function record(
        string $action,
        ?User $user = null,
        ?Cabinet $cabinet = null,
        ?Model $auditable = null,
        array $metadata = [],
        ?Request $request = null,
    ): AuditLog {
        $request ??= request();

        return AuditLog::query()->create([
            'cabinet_id' => $cabinet?->id ?? $user?->cabinet_id,
            'user_id' => $user?->id,
            'action' => $action,
            'auditable_type' => $auditable?->getMorphClass(),
            'auditable_id' => $auditable?->getKey(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => $metadata,
        ]);
    }
}
