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
        $comment = $this->comment->loadMissing(['post', 'commentable']);
        $subject = $comment->subject();
        $siteName = (string) SiteSettings::get('site_name', 'Hâcer İlim ve Kültür Derneği');
        $name = e($comment->fullName());
        $title = e($subject?->title ?? $comment->kindLabel());
        $pageUrl = $comment->pageUrl('yorumlar');
        $place = $comment->placePhrase();
        $visibility = $comment->hide_name
            ? 'Adınız sitede “Ziyaretçi” olarak görünür.'
            : 'Adınız yorumun yanında görünür.';

        $text = "Merhaba {$comment->fullName()},\n\n"
            ."“{$subject?->title}” başlıklı {$place} için yazdığınız yorum onaylanmıştır ve sitede yayındadır.\n"
            ."{$visibility}\n"
            .($pageUrl ? "{$pageUrl}\n\n" : "\n")
            ."Saygılarımızla,\n{$siteName}";

        $html = MailTemplate::render([
            'title' => 'Yorumunuz yayınlandı',
            'preheader' => 'Yorumunuz '.$place.' altında yayındadır.',
            'eyebrow' => $comment->sectionLabel(),
            'greeting' => "Merhaba {$name},",
            'intro' => '<p style="margin:0;">Yorumunuz onaylanmıştır. Artık '.e($place).' altında yayınlanmaktadır. '.e($visibility).'</p>',
            'highlight' => '<p style="margin:0 0 6px;font-family:\'Segoe UI\',Arial,sans-serif;font-size:10px;font-weight:600;letter-spacing:0.16em;text-transform:uppercase;color:#8a7a62;">'.e($comment->kindLabel()).'</p>'
                .'<p style="margin:0;font-family:Georgia,\'Times New Roman\',serif;font-size:16px;line-height:1.4;color:#161513;">'.$title.'</p>',
            'body' => '<p style="margin:0;">Yorumunuzu sitedeki sayfasından okuyabilirsiniz.</p>',
            'closing' => 'Saygılarımızla,<br><strong>'.e($siteName).'</strong>',
            'cta_label' => 'Yorumu görüntüle',
            'cta_url' => $pageUrl,
        ]);

        return [
            'to' => [$comment->email],
            'subject' => 'Yorumunuz yayınlandı',
            'html' => $html,
            'text' => $text,
        ];
    }
}
