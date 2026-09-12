<?php

namespace App\Support;

use Illuminate\Http\RedirectResponse;

class FormStatus
{
    public static function redirect(string $message, string $context): RedirectResponse
    {
        return back()
            ->with('status', $message)
            ->with('status_context', $context)
            ->withFragment('form-status-'.$context);
    }
}
