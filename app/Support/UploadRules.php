<?php

namespace App\Support;

class UploadRules
{
    public const IMAGE_MIMES = ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml'];

    public const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'svg'];

    public const AUDIO_MIMES = ['audio/mpeg', 'audio/mp4', 'audio/wav', 'audio/ogg'];

    public const MAX_IMAGE_KB = 2048;

    public const MAX_AUDIO_KB = 10240;
}
