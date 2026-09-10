<?php

namespace App\Support;

use Illuminate\Http\Request;

class FormGuard
{
    /**
     * Gizli honeypot alanı doluysa istek bottur; kayıt yazılmaz.
     */
    public static function isBot(Request $request): bool
    {
        return filled($request->input('website'));
    }
}
