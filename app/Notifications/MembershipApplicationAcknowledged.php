<?php

namespace App\Notifications;

use App\Models\MembershipApplication;
use App\Notifications\Channels\PhpMailerChannel;
use App\Notifications\Contracts\SendsViaPhpMailer;
use App\Support\MailTemplate;
use App\Support\SiteSettings;
use Illuminate\Notifications\Notification;

/**
 * Başvurana otomatik “başvurunuz iletildi” bilgisini gönderir.
 */
class MembershipApplicationAcknowledged extends Notification implements SendsViaPhpMailer
{
    public function __construct(public MembershipApplication $application) {}

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
        $name = e($this->application->name);

        $text = "Merhaba {$this->application->name},\n\n"
            ."Üyelik başvurunuz {$siteName}'ne iletilmiştir. Dernek yönetimi başvurunuzu inceleyecek ve size e-posta ile dönüş yapacaktır.\n\n"
            ."Saygılarımızla,\n{$siteName}";

        $html = MailTemplate::render([
            'title' => 'Başvurunuz iletildi',
            'preheader' => "Üyelik başvurunuz {$siteName}'ne iletildi.",
            'eyebrow' => 'Üyelik',
            'greeting' => "Merhaba {$name},",
            'intro' => '<p style="margin:0;">Üyelik başvurunuz <strong style="color:#161513;">'.$siteName.'</strong>\'ne iletilmiştir. Dernek yönetimi başvurunuzu inceleyecek ve size e-posta ile dönüş yapacaktır.</p>',
            'highlight' => '<p style="margin:0 0 6px;font-family:\'Segoe UI\',Arial,sans-serif;font-size:10px;font-weight:600;letter-spacing:0.16em;text-transform:uppercase;color:#8a7a62;">Başvuru</p>'
                .'<p style="margin:0;font-family:Georgia,\'Times New Roman\',serif;font-size:16px;line-height:1.4;color:#161513;">Üyelik / gönüllü</p>',
            'body' => '<p style="margin:0;">Bu otomatik bir bilgilendirmedir. Yanıtınızı dernek yönetimi e-posta yoluyla iletecektir.</p>',
            'closing' => 'Saygılarımızla,<br><strong>'.$siteName.'</strong>',
            'cta_label' => 'Web sitemizi ziyaret edin',
            'cta_url' => MailTemplate::publicBaseUrl().'/',
        ]);

        return [
            'to' => [$this->application->email],
            'subject' => "Başvurunuz {$siteName}'ne iletildi",
            'html' => $html,
            'text' => $text,
        ];
    }
}
