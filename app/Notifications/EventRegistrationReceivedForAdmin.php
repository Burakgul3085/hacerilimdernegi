<?php

namespace App\Notifications;

use App\Models\EventRegistration;
use App\Notifications\Channels\PhpMailerChannel;
use App\Notifications\Contracts\SendsViaPhpMailer;
use App\Support\MailTemplate;
use App\Support\PhpMailerClient;
use App\Support\SiteSettings;
use Illuminate\Notifications\Notification;

/**
 * Yeni program başvurusunu yöneticiye bildirir.
 */
class EventRegistrationReceivedForAdmin extends Notification implements SendsViaPhpMailer
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
        $recipient = app(PhpMailerClient::class)->otpRecipient(
            trim((string) SiteSettings::get('mailer_username', '')),
        );
        $siteName = (string) SiteSettings::get('site_name', config('app.name'));
        $programTitle = $this->registration->subjectTitle();
        $phone = $this->registration->phone ?: '—';
        $notes = $this->registration->notes ?: '—';
        $name = e($this->registration->name);
        $email = e($this->registration->email);
        $phoneEscaped = e($phone);
        $programTitleHtml = e($programTitle);
        $body = nl2br(e($notes));
        $panelUrl = MailTemplate::publicBaseUrl().'/yonetim';

        $text = "{$siteName} sitesinden yeni program başvurusu\n\n"
            ."Program: {$programTitle}\n"
            ."Ad: {$this->registration->name}\n"
            ."E-posta: {$this->registration->email}\n"
            ."Telefon: {$phone}\n\n"
            .$notes;

        $details = <<<HTML
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="font-family:'Segoe UI',Arial,sans-serif;font-size:14px;color:#3a3733;">
                <tr>
                    <td style="padding:0 0 10px;width:88px;color:#8a7a62;font-size:12px;letter-spacing:0.12em;text-transform:uppercase;">Program</td>
                    <td style="padding:0 0 10px;color:#161513;">{$programTitleHtml}</td>
                </tr>
                <tr>
                    <td style="padding:0 0 10px;color:#8a7a62;font-size:12px;letter-spacing:0.12em;text-transform:uppercase;">Ad</td>
                    <td style="padding:0 0 10px;color:#161513;">{$name}</td>
                </tr>
                <tr>
                    <td style="padding:0 0 10px;color:#8a7a62;font-size:12px;letter-spacing:0.12em;text-transform:uppercase;">E-posta</td>
                    <td style="padding:0 0 10px;"><a href="mailto:{$email}" style="color:#161513;text-decoration:underline;">{$email}</a></td>
                </tr>
                <tr>
                    <td style="padding:0;color:#8a7a62;font-size:12px;letter-spacing:0.12em;text-transform:uppercase;">Telefon</td>
                    <td style="padding:0;color:#161513;">{$phoneEscaped}</td>
                </tr>
            </table>
            HTML;

        $html = MailTemplate::render([
            'title' => 'Yeni program başvurusu',
            'preheader' => "{$this->registration->name} «{$programTitle}» programına başvurdu.",
            'eyebrow' => 'Yönetim bildirimi',
            'intro' => '<p style="margin:0;">Web sitesi program formundan yeni bir katılım başvurusu alındı.</p>',
            'highlight' => $details,
            'body' => '<p style="margin:0 0 8px;font-family:\'Segoe UI\',Arial,sans-serif;font-size:11px;font-weight:600;letter-spacing:0.18em;text-transform:uppercase;color:#8a7a62;">Not</p>'
                .'<div style="font-family:\'Segoe UI\',Arial,sans-serif;font-size:15px;line-height:1.75;color:#3a3733;">'.$body.'</div>',
            'closing' => 'Yanıtlamak için yönetim panelindeki <strong>Program kayıtları</strong> bölümünü kullanın.',
            'cta_label' => 'Panele git',
            'cta_url' => $panelUrl,
        ]);

        return [
            'to' => [$recipient],
            'subject' => "Yeni program başvurusu: {$this->registration->name} — {$programTitle}",
            'html' => $html,
            'text' => $text,
            'reply_to' => $this->registration->email,
            'reply_to_name' => $this->registration->name,
        ];
    }
}
