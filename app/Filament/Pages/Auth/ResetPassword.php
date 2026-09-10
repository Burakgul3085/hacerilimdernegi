<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\PasswordReset\ResetPassword as BaseResetPassword;
use Illuminate\Contracts\Support\Htmlable;

class ResetPassword extends BaseResetPassword
{
    public function getTitle(): string|Htmlable
    {
        return 'Şifre sıfırlama';
    }

    public function getHeading(): string|Htmlable|null
    {
        return 'Yeni şifre belirleyin';
    }
}
