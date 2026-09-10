<?php

namespace App\Notifications;

use App\Models\MembershipApplication;
use App\Notifications\Channels\PhpMailerChannel;
use App\Notifications\Contracts\SendsViaPhpMailer;
use App\Support\MailTemplate;
use App\Support\PhpMailerClient;
use App\Support\SiteSettings;
use Illuminate\Notifications\Notification;

/**
 * Yeni üyelik başvurusunu yöneticiye bildirir.
 */
class MembershipApplicationReceivedForAdmin extends Notification implements SendsViaPhpMailer
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
        $recipient = app(PhpMailerClient::class)->otpRecipient(
            trim((string) SiteSettings::get('mailer_username', '')),
        );
        $siteName = (string) SiteSettings::get('site_name', config('app.name'));
        $phone = $this->application->phone ?: '—';
        $city = $this->application->city ?: '—';
        $message = $this->application->message ?: '—';
        $name = e($this->application->name);
        $email = e($this->application->email);
        $phoneEscaped = e($phone);
        $cityEscaped = e($city);
        $body = nl2br(e($message));
        $panelUrl = MailTemplate::publicBaseUrl().'/yonetim';

        $text = "{$siteName} sitesinden yeni üyelik başvurusu\n\n"
            ."Ad: {$this->application->name}\n"
            ."E-posta: {$this->application->email}\n"
            ."Telefon: {$phone}\n"
            ."Şehir: {$city}\n\n"
            .$message;

        $details = <<<HTML
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="font-family:'Segoe UI',Arial,sans-serif;font-size:14px;color:#3a3733;">
                <tr>
                    <td style="padding:0 0 10px;width:88px;color:#8a7a62;font-size:12px;letter-spacing:0.12em;text-transform:uppercase;">Ad</td>
                    <td style="padding:0 0 10px;color:#161513;">{$name}</td>
                </tr>
                <tr>
                    <td style="padding:0 0 10px;color:#8a7a62;font-size:12px;letter-spacing:0.12em;text-transform:uppercase;">E-posta</td>
                    <td style="padding:0 0 10px;"><a href="mailto:{$email}" style="color:#161513;text-decoration:underline;">{$email}</a></td>
                </tr>
                <tr>
                    <td style="padding:0 0 10px;color:#8a7a62;font-size:12px;letter-spacing:0.12em;text-transform:uppercase;">Telefon</td>
                    <td style="padding:0 0 10px;color:#161513;">{$phoneEscaped}</td>
                </tr>
                <tr>
                    <td style="padding:0;color:#8a7a62;font-size:12px;letter-spacing:0.12em;text-transform:uppercase;">Şehir</td>
                    <td style="padding:0;color:#161513;">{$cityEscaped}</td>
                </tr>
            </table>
            HTML;

        $html = MailTemplate::render([
            'title' => 'Yeni üyelik başvurusu',
            'preheader' => "{$this->application->name} üyelik başvurusu gönderdi.",
            'eyebrow' => 'Yönetim bildirimi',
            'intro' => '<p style="margin:0;">Web sitesi üyelik formundan yeni bir başvuru alındı.</p>',
            'highlight' => $details,
            'body' => '<p style="margin:0 0 8px;font-family:\'Segoe UI\',Arial,sans-serif;font-size:11px;font-weight:600;letter-spacing:0.18em;text-transform:uppercase;color:#8a7a62;">Mesaj</p>'
                .'<div style="font-family:\'Segoe UI\',Arial,sans-serif;font-size:15px;line-height:1.75;color:#3a3733;">'.$body.'</div>',
            'closing' => 'Yanıtlamak için yönetim panelindeki <strong>Üyelik başvuruları</strong> bölümünü kullanın.',
            'cta_label' => 'Panele git',
            'cta_url' => $panelUrl,
        ]);

        return [
            'to' => [$recipient],
            'subject' => "Yeni üyelik başvurusu: {$this->application->name}",
            'html' => $html,
            'text' => $text,
            'reply_to' => $this->application->email,
            'reply_to_name' => $this->application->name,
        ];
    }
}
