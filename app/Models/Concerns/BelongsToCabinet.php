<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Cabinet;
use App\Models\Scopes\CabinetScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToCabinet
{
    /**
     * Boot the trait to attach the CabinetScope and auto-assign cabinet_id.
     */
    public static function bootBelongsToCabinet(): void
    {
        static::addGlobalScope(new CabinetScope);

        static::creating(function (Model $model): void {
            if (empty($model->getAttribute('cabinet_id')) && auth()->check()) {
                $user = auth()->user();
                if ($user && $user->cabinet_id !== null) {
                    $model->setAttribute('cabinet_id', $user->cabinet_id);
                }
            }
        });
    }

    /**
     * @return BelongsTo<Cabinet, static>
     */
    public function cabinet(): BelongsTo
    {
        return $this->belongsTo(Cabinet::class);
    }

    /**
     * Retrieve the model for a bound value without the cabinet scope
     * so that authorization policies can handle cross-cabinet 403 responses.
     *
     * @param  Model|Builder<Model>  $query
     * @param  mixed  $value
     * @param  string|null  $field
     * @return Builder<Model>
     */
    public function resolveRouteBindingQuery($query, $value, $field = null)
    {
        return parent::resolveRouteBindingQuery(
            $query->withoutGlobalScope(CabinetScope::class),
            $value,
            $field
        );
    }

    /**
     * Scope query to bypass cabinet isolation when explicitly required.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeWithoutCabinetScope(Builder $query): Builder
    {
        return $query->withoutGlobalScope(CabinetScope::class);
    }
}
