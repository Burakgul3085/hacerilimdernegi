<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Category extends Model
{
    use Auditable;

    protected $fillable = ['name', 'slug', 'type'];

    protected static function booted(): void
    {
        static::saving(function (Category $category): void {
            if (blank($category->slug) && filled($category->name)) {
                $category->slug = Str::slug($category->name);
            }
        });
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public static function findOrCreateIdByName(?string $name): ?int
    {
        $name = trim((string) $name);

        if ($name === '') {
            return null;
        }

        $slug = Str::slug($name);

        if ($slug === '') {
            $slug = Str::lower(Str::random(8));
        }

        $category = static::query()
            ->where(function (Builder $query) use ($name, $slug): void {
                $query->where('name', $name)->orWhere('slug', $slug);
            })
            ->first();

        if ($category) {
            return $category->id;
        }

        return static::query()->create([
            'name' => $name,
            'slug' => $slug,
            'type' => 'post',
        ])->id;
    }
}
