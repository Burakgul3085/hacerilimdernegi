<?php

namespace App\Notifications;

use App\Models\EventRegistration;
use App\Notifications\Channels\PhpMailerChannel;
use App\Notifications\Contracts\SendsViaPhpMailer;
use App\Support\MailTemplate;
use App\Support\SiteSettings;
use Illuminate\Notifications\Notification;

/**
 * Başvurana otomatik “program başvurunuz alınmıştır” bilgisini gönderir.
 */
class EventRegistrationAcknowledged extends Notification implements SendsViaPhpMailer
{
    public function __construct(public EventRegistration $registration) {}

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
        $programTitle = $this->registration->subjectTitle();
        $name = e($this->registration->name);
        $programTitleHtml = e($programTitle);

        $text = "Merhaba {$this->registration->name},\n\n"
            ."«{$programTitle}» programı için başvurunuz alınmıştır. En kısa zamanda size e-posta ile dönüş yapacağız.\n\n"
            ."Saygılarımızla,\n{$siteName}";

        $html = MailTemplate::render([
            'title' => 'Program başvurunuz alındı',
            'preheader' => "«{$programTitle}» programı için başvurunuz alınmıştır.",
            'eyebrow' => 'Program',
            'greeting' => "Merhaba {$name},",
            'intro' => '<p style="margin:0;"><strong style="color:#161513;">'.$programTitleHtml.'</strong> programı için başvurunuz alınmıştır. En kısa zamanda size e-posta ile dönüş yapacağız.</p>',
            'highlight' => '<p style="margin:0 0 6px;font-family:\'Segoe UI\',Arial,sans-serif;font-size:10px;font-weight:600;letter-spacing:0.16em;text-transform:uppercase;color:#8a7a62;">Program</p>'
                .'<p style="margin:0;font-family:Georgia,\'Times New Roman\',serif;font-size:16px;line-height:1.4;color:#161513;">'.$programTitleHtml.'</p>',
            'body' => '<p style="margin:0;">Bu otomatik bir bilgilendirmedir. Yanıtınızı dernek yönetimi e-posta yoluyla iletecektir.</p>',
            'closing' => 'Saygılarımızla,<br><strong>'.e($siteName).'</strong>',
            'cta_label' => 'Web sitemizi ziyaret edin',
            'cta_url' => MailTemplate::publicBaseUrl().'/',
        ]);

        return [
            'to' => [$this->registration->email],
            'subject' => "Program başvurunuz alındı: {$programTitle}",
            'html' => $html,
            'text' => $text,
        ];
    }
}
