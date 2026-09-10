<?php

namespace App\Notifications;

use App\Models\ContactMessage;
use App\Notifications\Channels\PhpMailerChannel;
use App\Notifications\Contracts\SendsViaPhpMailer;
use App\Support\MailTemplate;
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
        $subject = e($this->message->subject ?: 'İletişim formu');

        $text = "Merhaba {$this->message->name},\n\n"
            ."Mesajınız {$siteName}'ne ulaşmıştır. En kısa zamanda dönüş sağlayacağız.\n\n"
            ."Saygılarımızla,\n{$siteName}";

        $html = MailTemplate::render([
            'title' => 'Mesajınız bize ulaştı',
            'preheader' => "Mesajınız {$siteName}'ne iletildi.",
            'eyebrow' => 'İletişim',
            'greeting' => "Merhaba {$name},",
            'intro' => '<p style="margin:0;">Mesajınız <strong style="color:#161513;">'.$siteName.'</strong>\'ne ulaşmıştır. En kısa zamanda dönüş sağlayacağız.</p>',
            'highlight' => '<p style="margin:0 0 6px;font-family:\'Segoe UI\',Arial,sans-serif;font-size:10px;font-weight:600;letter-spacing:0.16em;text-transform:uppercase;color:#8a7a62;">Konu</p>'
                .'<p style="margin:0;font-family:Georgia,\'Times New Roman\',serif;font-size:16px;line-height:1.4;color:#161513;">'.$subject.'</p>',
            'body' => '<p style="margin:0;">Bu otomatik bir bilgilendirmedir. Yanıtınızı dernek yönetimi e-posta yoluyla iletecektir.</p>',
            'closing' => 'Saygılarımızla,<br><strong>'.$siteName.'</strong>',
            'cta_label' => 'Web sitemizi ziyaret edin',
            'cta_url' => MailTemplate::publicBaseUrl().'/',
        ]);

        return [
            'to' => [$this->message->email],
            'subject' => "Mesajınız {$siteName}'ne ulaştı",
            'html' => $html,
            'text' => $text,
        ];
    }
}
