<?php

namespace App\Models\Concerns;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function (Model $model): void {
            static::writeAudit($model, 'created');
        });

        static::updated(function (Model $model): void {
            static::writeAudit($model, 'updated');
        });

        static::deleted(function (Model $model): void {
            static::writeAudit($model, 'deleted');
        });
    }

    protected static function writeAudit(Model $model, string $action): void
    {
        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            return;
        }

        AuditLog::query()->create([
            'user_id' => auth()->id(),
            'action' => $action,
            'model_type' => $model::class,
            'model_id' => $model->getKey(),
            'ip_address' => request()?->ip(),
            'user_agent' => substr((string) request()?->userAgent(), 0, 255),
            'properties' => method_exists($model, 'getChanges') ? $model->getChanges() : [],
        ]);
    }
}
