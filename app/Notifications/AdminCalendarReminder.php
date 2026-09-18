<?php

namespace App\Notifications;

use App\Models\AdminCalendarEntry;
use App\Notifications\Channels\PhpMailerChannel;
use App\Notifications\Contracts\SendsViaPhpMailer;
use App\Support\MailTemplate;
use App\Support\SiteSettings;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class AdminCalendarReminder extends Notification implements SendsViaPhpMailer
{
    public function __construct(public AdminCalendarEntry $entry) {}

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
        $timezone = (string) config('app.timezone');
        $starts = $entry->starts_at?->timezone($timezone);
        $ends = $entry->ends_at?->timezone($timezone);

        $when = $entry->all_day
            ? ($starts?->format('d.m.Y').' · Tüm gün')
            : trim(($starts?->format('d.m.Y H:i') ?? '')
                .($ends ? ' – '.$ends->format('H:i') : ''));

        $description = filled($entry->description)
            ? e(Str::limit(strip_tags((string) $entry->description), 400))
            : null;

        $calendarUrl = url('/yonetim/takvimim');

        $text = "Takvim hatırlatması: {$entry->title}\n"
            ."Zaman: {$when}\n"
            .($description ? "Not: {$entry->description}\n" : '')
            ."Panel: {$calendarUrl}\n\n"
            .$siteName;

        $html = MailTemplate::render([
            'title' => 'Takvim hatırlatması',
            'preheader' => $entry->title.' · '.$when,
            'eyebrow' => 'Takvimim',
            'greeting' => 'Merhaba'.(isset($notifiable->name) ? ' '.e((string) $notifiable->name) : '').',',
            'intro' => '<p style="margin:0;">Takviminizdeki notun zamanı yaklaştı.</p>',
            'highlight' => '<p style="margin:0;font-family:Georgia,\'Times New Roman\',serif;font-size:20px;font-weight:700;color:#161513;">'.e($entry->title).'</p>'
                .'<p style="margin:8px 0 0;font-family:\'Segoe UI\',Arial,sans-serif;font-size:14px;color:#6b6560;">'.e($when).'</p>',
            'body' => $description
                ? '<p style="margin:0;">'.$description.'</p>'
                : '<p style="margin:0;">Detay için panellerinizdeki takvimi açabilirsiniz.</p>',
            'closing' => 'Saygılarımızla,<br><strong>'.$siteName.'</strong>',
            'cta_label' => 'Takvimimde aç',
            'cta_url' => $calendarUrl,
        ]);

        $to = is_object($notifiable) && isset($notifiable->email)
            ? [(string) $notifiable->email]
            : [];

        return [
            'to' => $to,
            'subject' => '[Hâcer] Takvim: '.$entry->title,
            'html' => $html,
            'text' => $text,
        ];
    }
}
