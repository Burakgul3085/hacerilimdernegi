<?php

namespace App\Models\Concerns;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function (Model $model): void {
            AuditLog::recordFromModel($model, 'created');
        });

        static::updated(function (Model $model): void {
            AuditLog::recordFromModel($model, 'updated');
        });

        static::deleted(function (Model $model): void {
            AuditLog::recordFromModel($model, 'deleted');
        });
    }
}
