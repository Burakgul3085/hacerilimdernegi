<?php

namespace App\Notifications;

use App\Models\PostComment;
use App\Notifications\Channels\PhpMailerChannel;
use App\Notifications\Contracts\SendsViaPhpMailer;
use App\Support\MailTemplate;
use App\Support\SiteSettings;
use Illuminate\Notifications\Notification;

/**
 * Yorum yazan kişiye alındı bilgisini gönderir.
 */
class PostCommentAcknowledged extends Notification implements SendsViaPhpMailer
{
    public function __construct(public PostComment $comment) {}

    /**
     * @return list<class-string>
     */
    public function via(object $notifiable): array
    {
        return [PhpMailerChannel::class];
    }

    public function toPhpMailer(object $notifiable): array
    {
        $comment = $this->comment->loadMissing('post');
        $post = $comment->post;
        $siteName = (string) SiteSettings::get('site_name', 'Hâcer İlim ve Kültür Derneği');
        $name = e($comment->fullName());
        $title = e($post?->title ?? 'Yazı');
        $postUrl = $post?->publicUrl();

        $text = "Merhaba {$comment->fullName()},\n\n"
            ."“{$post?->title}” başlıklı metne yazdığınız yorum {$siteName}'ne ulaşmıştır. "
            ."Yönetici onayından sonra yazının altında yayınlanır.\n\n"
            ."Saygılarımızla,\n{$siteName}";

        $html = MailTemplate::render([
            'title' => 'Yorumunuz alındı',
            'preheader' => 'Yorumunuz yönetici onayından sonra yayınlanır.',
            'eyebrow' => "Kalemim'İZ",
            'greeting' => "Merhaba {$name},",
            'intro' => '<p style="margin:0;">Yorumunuz <strong style="color:#161513;">'.e($siteName).'</strong>\'ne ulaşmıştır. Yönetici onayından sonra yazının altında yayınlanır.</p>',
            'highlight' => '<p style="margin:0 0 6px;font-family:\'Segoe UI\',Arial,sans-serif;font-size:10px;font-weight:600;letter-spacing:0.16em;text-transform:uppercase;color:#8a7a62;">Yazı</p>'
                .'<p style="margin:0;font-family:Georgia,\'Times New Roman\',serif;font-size:16px;line-height:1.4;color:#161513;">'.$title.'</p>',
            'body' => '<p style="margin:0;">Bu otomatik bir bilgilendirmedir. Onaylandığında size yeniden yazılır.</p>',
            'closing' => 'Saygılarımızla,<br><strong>'.e($siteName).'</strong>',
            'cta_label' => 'Yazıyı aç',
            'cta_url' => $postUrl,
        ]);

        return [
            'to' => [$comment->email],
            'subject' => 'Yorumunuz alındı',
            'html' => $html,
            'text' => $text,
        ];
    }
}
