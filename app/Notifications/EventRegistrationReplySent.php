<?php

namespace App\Notifications;

use App\Models\EventRegistration;
use App\Models\EventRegistrationReply;
use App\Notifications\Channels\PhpMailerChannel;
use App\Notifications\Contracts\SendsViaPhpMailer;
use App\Support\MailTemplate;
use App\Support\SiteSettings;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * Yönetim panelinden yazılan yanıtı etkinlik başvurana gönderir.
 */
class EventRegistrationReplySent extends Notification implements SendsViaPhpMailer
{
    public function __construct(
        public EventRegistration $registration,
        public EventRegistrationReply $reply,
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
        $this->registration->loadMissing('event');

        $siteName = (string) SiteSettings::get('site_name', 'Hâcer İlim ve Kültür Derneği');
        $eventTitle = $this->registration->event?->title ?: 'etkinlik';
        $name = e($this->registration->name);
        $eventTitleHtml = e($eventTitle);
        $body = nl2br(e($this->reply->body));
        $original = e(Str::limit($this->registration->notes ?: '—', 400));

        $text = "Merhaba {$this->registration->name},\n\n"
            ."{$siteName} olarak «{$eventTitle}» etkinliği başvurunuza yanıtımız:\n\n"
            ."{$this->reply->body}\n\n"
            ."—\nBaşvuru notunuz:\n".($this->registration->notes ?: '—')."\n\n"
            ."Saygılarımızla,\n{$siteName}";

        $html = MailTemplate::render([
            'title' => 'Etkinlik başvurunuza yanıt',
            'preheader' => Str::limit(strip_tags($this->reply->body), 90),
            'eyebrow' => 'Etkinlik yanıtı',
            'greeting' => "Merhaba {$name},",
            'intro' => '<p style="margin:0;"><strong style="color:#161513;">'.$siteName.'</strong> olarak <strong style="color:#161513;">'.$eventTitleHtml.'</strong> etkinliği başvurunuza yanıtımız aşağıdadır.</p>',
            'highlight' => '<div style="font-family:\'Segoe UI\',Arial,sans-serif;font-size:15px;line-height:1.75;color:#161513;">'.$body.'</div>',
            'body' => '<p style="margin:0 0 8px;font-family:\'Segoe UI\',Arial,sans-serif;font-size:11px;font-weight:600;letter-spacing:0.18em;text-transform:uppercase;color:#8a7a62;">Başvuru notunuz</p>'
                .'<p style="margin:0;font-family:\'Segoe UI\',Arial,sans-serif;font-size:13px;line-height:1.7;color:#6b6560;white-space:pre-wrap;">'.$original.'</p>',
            'closing' => 'Saygılarımızla,<br><strong>'.$siteName.'</strong>',
            'cta_label' => 'Web sitemizi ziyaret edin',
            'cta_url' => MailTemplate::publicBaseUrl().'/',
        ]);

        return [
            'to' => [$this->registration->email],
            'subject' => "Re: Etkinlik başvurunuz — {$eventTitle}",
            'html' => $html,
            'text' => $text,
        ];
    }
}
