<?php

namespace App\Models\Concerns;

use App\Support\OpenGraphImage;

trait ProvidesShareCover
{
    abstract public function shareCoverPath(): ?string;

    /**
     * @return array{url: string, width: int|null, height: int|null, mime: string|null}|null
     */
    public function openGraphImage(): ?array
    {
        return OpenGraphImage::fromStoragePath($this->shareCoverPath());
    }
}
