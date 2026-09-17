<?php

namespace App\Notifications;

use App\Models\Post;
use App\Notifications\Channels\PhpMailerChannel;
use App\Notifications\Contracts\SendsViaPhpMailer;
use App\Support\MailTemplate;
use App\Support\SiteSettings;
use Illuminate\Notifications\Notification;

/**
 * Ziyaretçiye yazısının onaylandığını ve sitede yayınlandığını bildirir.
 */
class VisitorPostApproved extends Notification implements SendsViaPhpMailer
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
        $siteName = (string) SiteSettings::get('site_name', 'Hâcer İlim ve Kültür Derneği');
        $name = e($this->post->byline() ?: 'Merhaba');
        $kind = e($this->post->typeLabel());
        $title = e($this->post->title);
        $mailTitle = $this->post->isPoem() ? 'Şiiriniz onaylanmıştır' : 'Yazınız onaylanmıştır';
        $postUrl = $this->post->publicUrl();

        $text = "Merhaba {$this->post->byline()},\n\n"
            ."{$this->post->typeLabel()} metniniz onaylanmıştır. Kalemim'İZ sayfasında yayındadır:\n{$postUrl}\n\n"
            ."Saygılarımızla,\n{$siteName}";

        $html = MailTemplate::render([
            'title' => $mailTitle,
            'preheader' => "{$this->post->typeLabel()} metniniz sitede yayındadır.",
            'eyebrow' => "Kalemim'İZ",
            'greeting' => "Merhaba {$name},",
            'intro' => '<p style="margin:0;">'.$kind.' metniniz onaylanmıştır. Artık <strong style="color:#161513;">Kalemim\'İZ</strong> sayfasında yayınlanmaktadır.</p>',
            'highlight' => '<p style="margin:0 0 6px;font-family:\'Segoe UI\',Arial,sans-serif;font-size:10px;font-weight:600;letter-spacing:0.16em;text-transform:uppercase;color:#8a7a62;">'.$kind.'</p>'
                .'<p style="margin:0;font-family:Georgia,\'Times New Roman\',serif;font-size:16px;line-height:1.4;color:#161513;">'.$title.'</p>',
            'body' => '<p style="margin:0;">Metninizi sitedeki sayfasından okuyabilir ve paylaşabilirsiniz.</p>',
            'closing' => 'Saygılarımızla,<br><strong>'.e($siteName).'</strong>',
            'cta_label' => 'Sayfada görüntüle',
            'cta_url' => $postUrl,
        ]);

        return [
            'to' => [$this->post->submitter_email],
            'subject' => $mailTitle,
            'html' => $html,
            'text' => $text,
        ];
    }
}
