<?php

namespace App\Models;

use App\Enums\ProgramType;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Program extends Model
{
    use Auditable;

    protected $fillable = [
        'type', 'title', 'slug', 'instructor', 'description', 'starts_at', 'ends_at', 'location', 'image', 'is_published',
    ];

    protected function casts(): array
    {
        return [
            'type' => ProgramType::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_published' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Program $program): void {
            if (blank($program->slug) && filled($program->title)) {
                $program->slug = Str::slug($program->title);
            }
        });
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where(function (Builder $builder): void {
            $builder->whereNull('starts_at')->orWhere('starts_at', '>=', now()->subDay());
        })->orderBy('starts_at');
    }
}
