<?php

namespace App\Actions;

use App\Enums\UserRole;
use App\Models\User;
use App\Notifications\AdminPasswordResetLink;
use App\Support\PhpMailerClient;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Password;
use RuntimeException;

/**
 * SuperAdmin için şifre sıfırlama token’ı üretir ve Mailer alıcısına gönderir.
 */
class SendAdminPasswordResetLink
{
    public function __construct(private PhpMailerClient $mailer) {}

    public function handle(): void
    {
        if (! $this->mailer->isConfigured()) {
            throw new RuntimeException('Mailer ayarları eksik. Yönetim panelinden e-posta ve uygulama şifresini kaydedin.');
        }

        $recipient = $this->mailer->otpRecipient();

        if ($recipient === '') {
            throw new RuntimeException('Şifre sıfırlama için Mailer alıcı adresi tanımlı değil.');
        }

        $admin = User::query()
            ->where('role', UserRole::SuperAdmin)
            ->orderBy('id')
            ->first();

        if ($admin === null) {
            return;
        }

        $token = Password::broker()->createToken($admin);
        $url = Filament::getPanel('admin')->getResetPasswordUrl($token, $admin);

        $admin->notify(new AdminPasswordResetLink($url));
    }
}
