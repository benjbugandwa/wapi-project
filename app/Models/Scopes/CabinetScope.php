<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class CabinetScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param  Builder<Model>  $builder
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (! auth()->check()) {
            return;
        }

        /** @var User $user */
        $user = auth()->user();

        if ($user->isSystemAdmin()) {
            return;
        }

        if ($user->cabinet_id !== null) {
            $builder->where($model->getTable().'.cabinet_id', $user->cabinet_id);
        }
    }
}
