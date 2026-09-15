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
        $answers = $this->registration->answerItems();
        $notesFallback = trim((string) ($this->registration->notes ?: ''));
        $panelUrl = $this->registration->panelEditUrl();

        $details = MailTemplate::detailRows([
            ['label' => 'Program', 'value' => $programTitle],
            ['label' => 'Ad', 'value' => $this->registration->name],
            ['label' => 'E-posta', 'value' => $this->registration->email, 'href' => 'mailto:'.$this->registration->email],
            ['label' => 'Telefon', 'value' => $phone, 'href' => filled($this->registration->phone) ? 'tel:'.$this->registration->phone : null],
        ]);

        if ($answers !== []) {
            $bodyHtml = MailTemplate::answerBlocks($answers);
            $bodyText = MailTemplate::answersPlainText($answers);
        } else {
            $bodyHtml = '<p style="margin:0 0 8px;font-family:\'Segoe UI\',Arial,sans-serif;font-size:11px;font-weight:600;letter-spacing:0.18em;text-transform:uppercase;color:#8a7a62;">Not</p>'
                .'<div style="font-family:\'Segoe UI\',Arial,sans-serif;font-size:15px;line-height:1.75;color:#3a3733;">'
                .nl2br(e($notesFallback !== '' ? $notesFallback : '—'))
                .'</div>';
            $bodyText = $notesFallback !== '' ? $notesFallback : '—';
        }

        $text = "{$siteName} sitesinden yeni program başvurusu\n\n"
            ."Program: {$programTitle}\n"
            ."Ad: {$this->registration->name}\n"
            ."E-posta: {$this->registration->email}\n"
            ."Telefon: {$phone}\n\n"
            .$bodyText
            ."\n\nKayıt: {$panelUrl}";

        $html = MailTemplate::render([
            'title' => 'Yeni program başvurusu',
            'preheader' => "{$this->registration->name} «{$programTitle}» programına başvurdu.",
            'eyebrow' => 'Yönetim bildirimi',
            'intro' => '<p style="margin:0;">Web sitesi katılım formundan yeni bir başvuru alındı. Ayrıntıları aşağıda, tam dosyayı panelden inceleyebilirsiniz.</p>',
            'highlight' => $details,
            'body' => $bodyHtml,
            'closing' => 'Yanıtlamak için yönetim panelindeki <strong>Program kayıtları</strong> bölümünü kullanın.',
            'cta_label' => 'Başvuruyu aç',
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
