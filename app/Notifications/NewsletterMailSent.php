<?php

namespace App\Notifications;

use App\Models\NewsletterSubscriber;
use App\Notifications\Channels\PhpMailerChannel;
use App\Notifications\Contracts\SendsViaPhpMailer;
use App\Support\MailTemplate;
use App\Support\SiteSettings;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * Yönetim panelinden yazılan e-bülten metnini aboneye gönderir.
 */
class NewsletterMailSent extends Notification implements SendsViaPhpMailer
{
    public function __construct(
        public string $mailSubject,
        public string $mailBody,
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
        /** @var NewsletterSubscriber $notifiable */
        $siteName = (string) SiteSettings::get('site_name', 'Hâcer İlim ve Kültür Derneği');
        $safeBody = nl2br(e($this->mailBody));

        $text = "{$this->mailBody}\n\n"
            ."Saygılarımızla,\n{$siteName}";

        $html = MailTemplate::render([
            'title' => $this->mailSubject,
            'preheader' => Str::limit(strip_tags($this->mailBody), 90),
            'eyebrow' => 'E-bülten',
            'highlight' => '<div style="font-family:\'Segoe UI\',Arial,sans-serif;font-size:15px;line-height:1.75;color:#161513;">'.$safeBody.'</div>',
            'closing' => 'Saygılarımızla,<br><strong>'.e($siteName).'</strong>',
            'cta_label' => 'Web sitemizi ziyaret edin',
            'cta_url' => MailTemplate::publicBaseUrl().'/',
        ]);

        return [
            'to' => [$notifiable->email],
            'subject' => $this->mailSubject,
            'html' => $html,
            'text' => $text,
        ];
    }
}
