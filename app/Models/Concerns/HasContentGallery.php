<?php

namespace App\Models\Concerns;

use App\Support\ContentMedia;

trait HasContentGallery
{
    /**
     * @return list<string>
     */
    public function galleryPaths(): array
    {
        return ContentMedia::paths($this->gallery ?? []);
    }

    /**
     * @return list<string>
     */
    public function galleryImages(): array
    {
        return $this->galleryPaths();
    }

    /**
     * @return list<array{path: string, url: string, kind: 'image'|'video'|'audio'}>
     */
    public function galleryMedia(): array
    {
        return ContentMedia::items($this->gallery ?? []);
    }
}
