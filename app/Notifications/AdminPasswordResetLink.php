<?php

namespace App\Notifications;

use App\Notifications\Channels\PhpMailerChannel;
use App\Notifications\Contracts\SendsViaPhpMailer;
use App\Support\MailTemplate;
use App\Support\PhpMailerClient;
use App\Support\SiteSettings;
use Illuminate\Notifications\Notification;

class AdminPasswordResetLink extends Notification implements SendsViaPhpMailer
{
    public function __construct(public string $url) {}

    /**
     * @return list<class-string>
     */
    public function via(object $notifiable): array
    {
        return [PhpMailerChannel::class];
    }

    public function toPhpMailer(object $notifiable): array
    {
        $recipient = app(PhpMailerClient::class)->otpRecipient();
        $siteName = (string) SiteSettings::get('site_name', config('app.name'));
        $minutes = (int) config('auth.passwords.users.expire', 60);

        $text = "Yönetim paneli şifre sıfırlama bağlantınız:\n{$this->url}\n\n"
            ."Bu bağlantı {$minutes} dakika geçerlidir. İsteği siz yapmadıysanız bu iletiyi yok sayın.\n\n"
            .$siteName;

        $html = MailTemplate::render([
            'title' => 'Şifrenizi yenileyin',
            'preheader' => 'Yönetim paneli şifre sıfırlama bağlantısı.',
            'eyebrow' => 'Güvenlik',
            'greeting' => 'Merhaba,',
            'intro' => '<p style="margin:0;">'.$siteName.' yönetim paneli için bir şifre sıfırlama isteği aldık. Yeni şifrenizi belirlemek için aşağıdaki düğmeyi kullanın.</p>',
            'body' => '<p style="margin:0;">Bu bağlantı '.$minutes.' dakika geçerlidir. İsteği siz yapmadıysanız bu iletiyi yok sayın; mevcut şifreniz değişmez.</p>',
            'closing' => 'Saygılarımızla,<br><strong>'.$siteName.'</strong>',
            'cta_label' => 'Şifreyi yenile',
            'cta_url' => $this->url,
        ]);

        return [
            'to' => [$recipient],
            'subject' => 'Yönetim paneli şifre sıfırlama',
            'html' => $html,
            'text' => $text,
        ];
    }
}
