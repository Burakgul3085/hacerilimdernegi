<?php

namespace App\Notifications;

use App\Notifications\Channels\PhpMailerChannel;
use App\Notifications\Contracts\SendsViaPhpMailer;
use App\Support\PhpMailerClient;
use Illuminate\Notifications\Notification;
use SensitiveParameter;

class AdminLoginVerificationCode extends Notification implements SendsViaPhpMailer
{
    public function __construct(
        #[SensitiveParameter]
        public string $code,
        public int $codeExpiryMinutes,
    ) {}

    /**
     * @return list<class-string>
     */
    public function via(object $notifiable): array
    {
        return [PhpMailerChannel::class];
    }

    public function toPhpMailer(object $notifiable): array
    {
        $recipient = app(PhpMailerClient::class)->otpRecipient(
            is_object($notifiable) && isset($notifiable->email) ? (string) $notifiable->email : null,
        );

        $siteName = (string) config('app.name');
        $minutes = $this->codeExpiryMinutes;

        $text = "Yönetim paneli giriş doğrulama kodunuz: {$this->code}\n\nBu kod {$minutes} dakika geçerlidir. Kodu kimseyle paylaşmayın.";

        $html = <<<HTML
            <div style="font-family: Georgia, 'Times New Roman', serif; color: #161513; line-height: 1.6;">
                <p style="margin: 0 0 16px;">Merhaba,</p>
                <p style="margin: 0 0 16px;"><strong>{$siteName}</strong> yönetim paneline giriş için doğrulama kodunuz:</p>
                <p style="margin: 0 0 24px; font-size: 32px; letter-spacing: 0.35em; font-weight: 700;">{$this->code}</p>
                <p style="margin: 0 0 8px; color: #6b6560;">Bu kod {$minutes} dakika geçerlidir.</p>
                <p style="margin: 0; color: #6b6560;">Kodu kimseyle paylaşmayın. Bu isteği siz yapmadıysanız şifrenizi değiştirin.</p>
            </div>
            HTML;

        return [
            'to' => [$recipient],
            'subject' => "Yönetim paneli doğrulama kodu: {$this->code}",
            'html' => $html,
            'text' => $text,
        ];
    }
}
