<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait UsesUuid
{
    protected function initializeUsesUuid(): void
    {
        $this->keyType = 'string';
        $this->incrementing = false;
    }

    protected static function bootUsesUuid(): void
    {
        static::creating(static function (Model $model): void {
            if (! $model->getKey()) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    public function getIncrementing(): bool
    {
        return false;
    }

    public function getKeyType(): string
    {
        return 'string';
    }
}
