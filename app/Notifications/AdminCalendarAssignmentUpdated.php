<?php

namespace App\Notifications;

use App\Models\AdminCalendarEntry;
use App\Models\User;
use App\Notifications\Channels\PhpMailerChannel;
use App\Notifications\Contracts\SendsViaPhpMailer;
use App\Support\MailTemplate;
use App\Support\SiteSettings;
use Illuminate\Notifications\Notification;

class AdminCalendarAssignmentUpdated extends Notification implements SendsViaPhpMailer
{
    public function __construct(
        public AdminCalendarEntry $entry,
        public User $updatedBy,
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
        $siteName = (string) SiteSettings::get('site_name', config('app.name'));
        $entry = $this->entry;
        $status = $entry->assignment_status?->label() ?? 'Güncellendi';
        $calendarUrl = url('/yonetim/takvimim');
        $updater = $this->updatedBy->name;

        $text = "{$updater} atadığınız görevin durumunu güncelledi.\n"
            ."Görev: {$entry->title}\n"
            ."Yeni durum: {$status}\n"
            ."Panel: {$calendarUrl}\n\n"
            .$siteName;

        $html = MailTemplate::render([
            'title' => 'Görev durumu güncellendi',
            'preheader' => $updater.' · '.$status,
            'eyebrow' => 'Takvim görevi',
            'greeting' => 'Merhaba'.(isset($notifiable->name) ? ' '.e((string) $notifiable->name) : '').',',
            'intro' => '<p style="margin:0;"><strong>'.e($updater).'</strong> atadığınız görevin durumunu güncelledi.</p>',
            'highlight' => '<p style="margin:0;font-family:Georgia,\'Times New Roman\',serif;font-size:20px;font-weight:700;color:#161513;">'.e($entry->title).'</p>'
                .'<p style="margin:8px 0 0;font-family:\'Segoe UI\',Arial,sans-serif;font-size:14px;color:#6b6560;">Durum: '.e($status).'</p>',
            'body' => '<p style="margin:0;">Güncel hali Takvimim sayfasından görülebilir.</p>',
            'closing' => 'Saygılarımızla,<br><strong>'.$siteName.'</strong>',
            'cta_label' => 'Takvimimde aç',
            'cta_url' => $calendarUrl,
        ]);

        return [
            'to' => [is_object($notifiable) && isset($notifiable->email) ? (string) $notifiable->email : ''],
            'subject' => '[Hâcer] Görev durumu: '.$entry->title,
            'html' => $html,
            'text' => $text,
        ];
    }
}
