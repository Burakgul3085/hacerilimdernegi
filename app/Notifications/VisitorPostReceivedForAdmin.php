<?php

namespace App\Notifications;

use App\Models\Post;
use App\Notifications\Channels\PhpMailerChannel;
use App\Notifications\Contracts\SendsViaPhpMailer;
use App\Support\MailTemplate;
use App\Support\PhpMailerClient;
use App\Support\SiteSettings;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * Yeni ziyaretçi yazısını yöneticiye bildirir.
 */
class VisitorPostReceivedForAdmin extends Notification implements SendsViaPhpMailer
{
    public function __construct(public Post $post) {}

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
        $name = e($this->post->byline() ?: '—');
        $email = e((string) $this->post->submitter_email);
        $kind = e($this->post->typeLabel());
        $title = e($this->post->title);
        $excerpt = e(Str::limit($this->post->excerpt ?: strip_tags((string) $this->post->body), 280));
        $panelUrl = rtrim(MailTemplate::publicBaseUrl(), '/').'/yonetim/posts/'.$this->post->getKey().'/edit';

        $text = "{$siteName} sitesinden yeni {$this->post->typeLabel()}\n\n"
            ."Ad: {$this->post->byline()}\n"
            ."E-posta: {$this->post->submitter_email}\n"
            ."Başlık: {$this->post->title}\n\n"
            .strip_tags((string) $this->post->body);

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
                    <td style="padding:0 0 10px;color:#8a7a62;font-size:12px;letter-spacing:0.12em;text-transform:uppercase;">Tür</td>
                    <td style="padding:0 0 10px;color:#161513;">{$kind}</td>
                </tr>
                <tr>
                    <td style="padding:0;color:#8a7a62;font-size:12px;letter-spacing:0.12em;text-transform:uppercase;">Başlık</td>
                    <td style="padding:0;color:#161513;">{$title}</td>
                </tr>
            </table>
            HTML;

        $html = MailTemplate::render([
            'title' => 'Yeni '.$this->post->typeLabel().' gönderisi',
            'preheader' => ($this->post->byline() ?: 'Ziyaretçi').' bir '.$this->post->typeLabel().' gönderdi.',
            'eyebrow' => 'Yönetim bildirimi',
            'intro' => '<p style="margin:0;">Web sitesi yazılar ve şiirler formundan yeni bir gönderi alındı. Yayınlamak için panelden onaylayın.</p>',
            'highlight' => $details,
            'body' => '<p style="margin:0 0 8px;font-family:\'Segoe UI\',Arial,sans-serif;font-size:11px;font-weight:600;letter-spacing:0.18em;text-transform:uppercase;color:#8a7a62;">Özet</p>'
                .'<div style="font-family:\'Segoe UI\',Arial,sans-serif;font-size:15px;line-height:1.75;color:#3a3733;">'.$excerpt.'</div>',
            'closing' => 'Onaylamak için yönetim panelindeki <strong>Yazılar ve şiirler</strong> bölümünü kullanın.',
            'cta_label' => 'Gönderiyi aç',
            'cta_url' => $panelUrl,
        ]);

        return [
            'to' => [$recipient],
            'subject' => "Yeni {$this->post->typeLabel()}: {$this->post->title}",
            'html' => $html,
            'text' => $text,
            'reply_to' => $this->post->submitter_email,
            'reply_to_name' => $this->post->byline(),
        ];
    }
}
