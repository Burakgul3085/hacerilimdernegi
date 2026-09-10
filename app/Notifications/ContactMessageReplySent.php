<?php

namespace App\Notifications;

use App\Models\ContactMessage;
use App\Models\ContactMessageReply;
use App\Notifications\Channels\PhpMailerChannel;
use App\Notifications\Contracts\SendsViaPhpMailer;
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

        $html = <<<HTML
            <div style="font-family: Georgia, 'Times New Roman', serif; color: #161513; line-height: 1.6;">
                <p style="margin: 0 0 16px;">Merhaba {$name},</p>
                <p style="margin: 0 0 16px;"><strong>{$siteName}</strong> olarak mesajınıza yanıtımız:</p>
                <div style="padding: 16px; background: #f7f3eb; border-radius: 12px;">{$body}</div>
                <p style="margin: 24px 0 8px; color: #6b6560; font-size: 13px;">Orijinal mesajınız:</p>
                <p style="margin: 0; color: #6b6560; font-size: 13px; white-space: pre-wrap;">{$original}</p>
                <p style="margin: 24px 0 0;">Saygılarımızla,<br><strong>{$siteName}</strong></p>
            </div>
            HTML;

        return [
            'to' => [$this->message->email],
            'subject' => 'Re: '.$originalSubject,
            'html' => $html,
            'text' => $text,
        ];
    }
}
