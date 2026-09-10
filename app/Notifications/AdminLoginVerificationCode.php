<?php

namespace App\Notifications;

use App\Notifications\Channels\PhpMailerChannel;
use App\Notifications\Contracts\SendsViaPhpMailer;
use App\Support\MailTemplate;
use App\Support\PhpMailerClient;
use App\Support\SiteSettings;
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

        $siteName = (string) SiteSettings::get('site_name', config('app.name'));
        $minutes = $this->codeExpiryMinutes;
        $code = e($this->code);

        $text = "Yönetim paneli giriş doğrulama kodunuz: {$this->code}\n\n"
            ."Bu kod {$minutes} dakika geçerlidir. Kodu kimseyle paylaşmayın.\n\n"
            .$siteName;

        $html = MailTemplate::render([
            'title' => 'Doğrulama kodunuz',
            'preheader' => "Yönetim paneli kodu: {$this->code}",
            'eyebrow' => 'Güvenli giriş',
            'greeting' => 'Merhaba,',
            'intro' => '<p style="margin:0;">'.$siteName.' yönetim paneline giriş için tek kullanımlık doğrulama kodunuz aşağıdadır.</p>',
            'highlight' => '<p style="margin:0;text-align:center;font-family:Georgia,\'Times New Roman\',serif;font-size:28px;letter-spacing:0.42em;font-weight:700;color:#161513;">'.$code.'</p>'
                .'<p style="margin:10px 0 0;text-align:center;font-family:\'Segoe UI\',Arial,sans-serif;font-size:12px;color:#6b6560;">'.$minutes.' dakika geçerlidir</p>',
            'body' => '<p style="margin:0;">Bu kodu kimseyle paylaşmayın. Bu isteği siz yapmadıysanız şifrenizi değiştirin ve bu iletiyi dikkate almayın.</p>',
            'closing' => 'Saygılarımızla,<br><strong>'.$siteName.'</strong>',
        ]);

        return [
            'to' => [$recipient],
            'subject' => "Yönetim paneli doğrulama kodu: {$this->code}",
            'html' => $html,
            'text' => $text,
        ];
    }
}
