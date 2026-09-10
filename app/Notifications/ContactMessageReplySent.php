<?php

namespace App\Notifications;

use App\Models\ContactMessage;
use App\Models\ContactMessageReply;
use App\Notifications\Channels\PhpMailerChannel;
use App\Notifications\Contracts\SendsViaPhpMailer;
use App\Support\MailTemplate;
use App\Support\SiteSettings;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * Yönetim panelinden yazılan yanıtı ziyaretçiye gönderir.
 */
class ContactMessageReplySent extends Notification implements SendsViaPhpMailer
{
    public function __construct(
        public ContactMessage $message,
        public ContactMessageReply $reply,
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
        $siteName = (string) SiteSettings::get('site_name', 'Hâcer İlim ve Kültür Derneği');
        $originalSubject = $this->message->subject ?: 'İletişim mesajınız';
        $name = e($this->message->name);
        $body = nl2br(e($this->reply->body));
        $original = e(Str::limit($this->message->message, 400));

        $text = "Merhaba {$this->message->name},\n\n"
            ."{$siteName} olarak mesajınıza yanıtımız:\n\n"
            ."{$this->reply->body}\n\n"
            ."—\nOrijinal mesajınız:\n{$this->message->message}\n\n"
            ."Saygılarımızla,\n{$siteName}";

        $html = MailTemplate::render([
            'title' => 'Mesajınıza yanıt',
            'preheader' => Str::limit(strip_tags($this->reply->body), 90),
            'eyebrow' => 'İletişim yanıtı',
            'greeting' => "Merhaba {$name},",
            'intro' => '<p style="margin:0;"><strong style="color:#161513;">'.$siteName.'</strong> olarak mesajınıza yanıtımız aşağıdadır.</p>',
            'highlight' => '<div style="font-family:\'Segoe UI\',Arial,sans-serif;font-size:15px;line-height:1.75;color:#161513;">'.$body.'</div>',
            'body' => '<p style="margin:0 0 8px;font-family:\'Segoe UI\',Arial,sans-serif;font-size:11px;font-weight:600;letter-spacing:0.18em;text-transform:uppercase;color:#8a7a62;">Orijinal mesajınız</p>'
                .'<p style="margin:0;font-family:\'Segoe UI\',Arial,sans-serif;font-size:13px;line-height:1.7;color:#6b6560;white-space:pre-wrap;">'.$original.'</p>',
            'closing' => 'Saygılarımızla,<br><strong>'.$siteName.'</strong>',
            'cta_label' => 'Web sitemizi ziyaret edin',
            'cta_url' => MailTemplate::publicBaseUrl().'/',
        ]);

        return [
            'to' => [$this->message->email],
            'subject' => 'Re: '.$originalSubject,
            'html' => $html,
            'text' => $text,
        ];
    }
}
