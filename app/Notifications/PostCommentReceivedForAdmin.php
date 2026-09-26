<?php

namespace App\Notifications;

use App\Models\PostComment;
use App\Notifications\Channels\PhpMailerChannel;
use App\Notifications\Contracts\SendsViaPhpMailer;
use App\Support\MailTemplate;
use App\Support\PhpMailerClient;
use App\Support\SiteSettings;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * Yeni yazı yorumunu yöneticiye bildirir.
 */
class PostCommentReceivedForAdmin extends Notification implements SendsViaPhpMailer
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
        $recipient = app(PhpMailerClient::class)->otpRecipient(
            trim((string) SiteSettings::get('mailer_username', '')),
        );
        $siteName = (string) SiteSettings::get('site_name', config('app.name'));
        $name = e($comment->fullName());
        $email = e($comment->email);
        $title = e($post?->title ?? 'Yazı');
        $visibility = $comment->hide_name
            ? 'İsim sitede gizli kalacak.'
            : 'İsim sitede görünecek.';
        $excerpt = e(Str::limit($comment->body, 280));
        $panelUrl = rtrim(MailTemplate::publicBaseUrl(), '/').'/yonetim/posts/'.$comment->post_id.'/edit';

        $text = "{$siteName} sitesinden yeni yorum\n\n"
            .'Yazı: '.($post?->title ?? '—')."\n"
            ."Ad: {$comment->fullName()}\n"
            ."E-posta: {$comment->email}\n"
            ."{$visibility}\n\n"
            .$comment->body;

        $details = <<<HTML
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="font-family:'Segoe UI',Arial,sans-serif;font-size:14px;color:#3a3733;">
                <tr>
                    <td style="padding:0 0 10px;width:88px;color:#8a7a62;font-size:12px;letter-spacing:0.12em;text-transform:uppercase;">Yazı</td>
                    <td style="padding:0 0 10px;color:#161513;">{$title}</td>
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
                    <td style="padding:0;color:#8a7a62;font-size:12px;letter-spacing:0.12em;text-transform:uppercase;">Görünürlük</td>
                    <td style="padding:0;color:#161513;">{$visibility}</td>
                </tr>
            </table>
            HTML;

        $html = MailTemplate::render([
            'title' => 'Yeni yorum',
            'preheader' => $comment->fullName().' bir yazıya yorum yazdı.',
            'eyebrow' => 'Yönetim bildirimi',
            'intro' => '<p style="margin:0;">Kalemim\'İZ sayfasından yeni bir yorum alındı. Yayınlamak için panelden onaylayın.</p>',
            'highlight' => $details,
            'body' => '<p style="margin:0 0 8px;font-family:\'Segoe UI\',Arial,sans-serif;font-size:11px;font-weight:600;letter-spacing:0.18em;text-transform:uppercase;color:#8a7a62;">Yorum</p>'
                .'<div style="font-family:\'Segoe UI\',Arial,sans-serif;font-size:15px;line-height:1.75;color:#3a3733;">'.$excerpt.'</div>',
            'closing' => 'Onaylamak için yönetim panelindeki <strong>Kalemim\'İZ</strong> bölümünde ilgili yazıyı açın.',
            'cta_label' => 'Yazıyı aç',
            'cta_url' => $panelUrl,
        ]);

        return [
            'to' => [$recipient],
            'subject' => 'Yeni yorum: '.($post?->title ?? 'Kalemim\'İZ'),
            'html' => $html,
            'text' => $text,
            'reply_to' => $comment->email,
            'reply_to_name' => $comment->fullName(),
        ];
    }
}
