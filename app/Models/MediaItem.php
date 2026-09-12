<?php

namespace App\Models;

use App\Enums\MediaType;
use App\Models\Concerns\Auditable;
use App\Support\UploadRules;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MediaItem extends Model
{
    use Auditable;

    protected $fillable = [
        'media_album_id', 'type', 'title', 'path', 'external_url', 'caption', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'type' => MediaType::class,
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (MediaItem $item): void {
            if (filled($item->path)) {
                $item->type = UploadRules::typeFromPath((string) $item->path);
            }
        });
    }

    public function album(): BelongsTo
    {
        return $this->belongsTo(MediaAlbum::class, 'media_album_id');
    }
}
