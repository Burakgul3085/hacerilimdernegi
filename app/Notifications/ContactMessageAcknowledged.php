<?php

namespace App\Notifications;

use App\Models\ContactMessage;
use App\Notifications\Channels\PhpMailerChannel;
use App\Notifications\Contracts\SendsViaPhpMailer;
use App\Support\SiteSettings;
use Illuminate\Notifications\Notification;

/**
 * Ziyaretçiye otomatik “mesajınız bize ulaştı” bilgisini gönderir.
 */
class ContactMessageAcknowledged extends Notification implements SendsViaPhpMailer
{
    public function __construct(public ContactMessage $message) {}

    /**
     * @return list<class-string>
     */
    public function via(object $notifiable): array
    {
        return [PhpMailerChannel::class];
    }

    public function toPhpMailer(object $notifiable): array
    {
        $siteName = (string) SiteSettings::get('site_name', 'Hâcer İlim ve Kültür Derneği');
        $name = e($this->message->name);

        $text = "Merhaba {$this->message->name},\n\n"
            ."Mesajınız {$siteName}'ne ulaşmıştır. En kısa zamanda dönüş sağlayacağız.\n\n"
            ."Saygılarımızla,\n{$siteName}";

        $html = <<<HTML
            <div style="font-family: Georgia, 'Times New Roman', serif; color: #161513; line-height: 1.6;">
                <p style="margin: 0 0 16px;">Merhaba {$name},</p>
                <p style="margin: 0 0 16px;">Mesajınız <strong>{$siteName}</strong>'ne ulaşmıştır. En kısa zamanda dönüş sağlayacağız.</p>
                <p style="margin: 0;">Saygılarımızla,<br><strong>{$siteName}</strong></p>
            </div>
            HTML;

        return [
            'to' => [$this->message->email],
            'subject' => "Mesajınız {$siteName}'ne ulaştı",
            'html' => $html,
            'text' => $text,
        ];
    }
}
