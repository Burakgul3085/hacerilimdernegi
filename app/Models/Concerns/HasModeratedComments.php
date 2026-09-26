<?php

namespace App\Models\Concerns;

use App\Models\PostComment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasModeratedComments
{
    public function comments(): MorphMany
    {
        return $this->morphMany(PostComment::class, 'commentable');
    }

    public function pendingComments(): MorphMany
    {
        return $this->comments()->pending();
    }

    public function approvedComments(): MorphMany
    {
        return $this->comments()->approved()->oldest();
    }

    protected static function bootHasModeratedComments(): void
    {
        static::deleting(function (Model $model): void {
            $model->comments()->delete();
        });
    }
}
