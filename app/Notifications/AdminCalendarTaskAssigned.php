<?php

namespace App\Notifications;

use App\Models\AdminCalendarEntry;
use App\Notifications\Channels\PhpMailerChannel;
use App\Notifications\Contracts\SendsViaPhpMailer;
use App\Support\MailTemplate;
use App\Support\SiteSettings;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class AdminCalendarTaskAssigned extends Notification implements SendsViaPhpMailer
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
        $entry = $this->entry->loadMissing('creator');
        $timezone = (string) config('app.timezone');
        $starts = $entry->starts_at?->timezone($timezone);
        $assigner = $entry->creator?->name ?? 'Bir süper yönetici';

        $when = $entry->all_day
            ? ($starts?->format('d.m.Y').' · Tüm gün')
            : ($starts?->format('d.m.Y H:i') ?? '');

        $calendarUrl = url('/yonetim/takvimim');
        $description = filled($entry->description)
            ? e(Str::limit(strip_tags((string) $entry->description), 400))
            : null;

        $text = "{$assigner} size bir görev atadı.\n"
            ."Başlık: {$entry->title}\n"
            ."Zaman: {$when}\n"
            ."Panel: {$calendarUrl}\n\n"
            .$siteName;

        $html = MailTemplate::render([
            'title' => 'Size görev atandı',
            'preheader' => $assigner.' · '.$entry->title,
            'eyebrow' => 'Takvim görevi',
            'greeting' => 'Merhaba'.(isset($notifiable->name) ? ' '.e((string) $notifiable->name) : '').',',
            'intro' => '<p style="margin:0;"><strong>'.e($assigner).'</strong> size bir takvim görevi atadı.</p>',
            'highlight' => '<p style="margin:0;font-family:Georgia,\'Times New Roman\',serif;font-size:20px;font-weight:700;color:#161513;">'.e($entry->title).'</p>'
                .'<p style="margin:8px 0 0;font-family:\'Segoe UI\',Arial,sans-serif;font-size:14px;color:#6b6560;">'.e($when).'</p>',
            'body' => $description
                ? '<p style="margin:0;">'.$description.'</p>'
                : '<p style="margin:0;">Detay için Takvimim sayfasını açabilirsiniz.</p>',
            'closing' => 'Saygılarımızla,<br><strong>'.$siteName.'</strong>',
            'cta_label' => 'Takvimimde aç',
            'cta_url' => $calendarUrl,
        ]);

        return [
            'to' => [is_object($notifiable) && isset($notifiable->email) ? (string) $notifiable->email : ''],
            'subject' => '[Hâcer] Görev atandı: '.$entry->title,
            'html' => $html,
            'text' => $text,
        ];
    }
}
