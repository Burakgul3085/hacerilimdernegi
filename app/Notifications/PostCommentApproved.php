<?php

namespace App\Notifications;

use App\Models\PostComment;
use App\Notifications\Channels\PhpMailerChannel;
use App\Notifications\Contracts\SendsViaPhpMailer;
use App\Support\MailTemplate;
use App\Support\SiteSettings;
use Illuminate\Notifications\Notification;

/**
 * Onaylanan yorumun sahibine yayınlandığını bildirir.
 */
class PostCommentApproved extends Notification implements SendsViaPhpMailer
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
        $postUrl = $post ? $post->publicUrl().'#yorumlar' : null;
        $visibility = $comment->hide_name
            ? 'Adınız sitede “Ziyaretçi” olarak görünür.'
            : 'Adınız yorumun yanında görünür.';

        $text = "Merhaba {$comment->fullName()},\n\n"
            ."“{$post?->title}” başlıklı metne yazdığınız yorum onaylanmıştır ve sitede yayındadır.\n"
            ."{$visibility}\n"
            .($postUrl ? "{$postUrl}\n\n" : "\n")
            ."Saygılarımızla,\n{$siteName}";

        $html = MailTemplate::render([
            'title' => 'Yorumunuz yayınlandı',
            'preheader' => 'Yorumunuz yazının altında yayındadır.',
            'eyebrow' => "Kalemim'İZ",
            'greeting' => "Merhaba {$name},",
            'intro' => '<p style="margin:0;">Yorumunuz onaylanmıştır. Artık yazının altında yayınlanmaktadır. '.e($visibility).'</p>',
            'highlight' => '<p style="margin:0 0 6px;font-family:\'Segoe UI\',Arial,sans-serif;font-size:10px;font-weight:600;letter-spacing:0.16em;text-transform:uppercase;color:#8a7a62;">Yazı</p>'
                .'<p style="margin:0;font-family:Georgia,\'Times New Roman\',serif;font-size:16px;line-height:1.4;color:#161513;">'.$title.'</p>',
            'body' => '<p style="margin:0;">Yorumunuzu sitedeki sayfasından okuyabilirsiniz.</p>',
            'closing' => 'Saygılarımızla,<br><strong>'.e($siteName).'</strong>',
            'cta_label' => 'Yorumu görüntüle',
            'cta_url' => $postUrl,
        ]);

        return [
            'to' => [$comment->email],
            'subject' => 'Yorumunuz yayınlandı',
            'html' => $html,
            'text' => $text,
        ];
    }
}
