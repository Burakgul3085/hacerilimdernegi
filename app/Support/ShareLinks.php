<?php

namespace App\Support;

final class ShareLinks
{
    /**
     * @return array{shareUrl: string, whatsappShareUrl: string}
     */
    public static function for(string $title, string $url): array
    {
        return [
            'shareUrl' => $url,
            'whatsappShareUrl' => 'https://wa.me/?text='.rawurlencode($title.' — '.$url),
        ];
    }
}
