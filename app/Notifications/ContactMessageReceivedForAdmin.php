<?php

namespace App\Notifications;

use App\Models\ContactMessage;
use App\Notifications\Channels\PhpMailerChannel;
use App\Notifications\Contracts\SendsViaPhpMailer;
use App\Support\PhpMailerClient;
use App\Support\SiteSettings;
use Illuminate\Notifications\Notification;

/**
 * Yeni iletişim formu mesajını yöneticiye (doğrulama kodunun gittiği adrese) bildirir.
 */
class ContactMessageReceivedForAdmin extends Notification implements SendsViaPhpMailer
{
    public function __construct(public ContactMessage $message) {}

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
        $subject = $this->message->subject ?: 'İletişim formu mesajı';
        $phone = $this->message->phone ?: '—';
        $body = e($this->message->message);
        $name = e($this->message->name);
        $email = e($this->message->email);

        $text = "{$siteName} sitesinden yeni iletişim mesajı\n\n"
            ."Ad: {$this->message->name}\n"
            ."E-posta: {$this->message->email}\n"
            ."Telefon: {$phone}\n"
            ."Konu: {$subject}\n\n"
            .$this->message->message;

        $html = <<<HTML
            <div style="font-family: Georgia, 'Times New Roman', serif; color: #161513; line-height: 1.6;">
                <p style="margin: 0 0 16px;"><strong>{$siteName}</strong> sitesinden yeni bir iletişim mesajı geldi.</p>
                <p style="margin: 0 0 8px;"><strong>Ad:</strong> {$name}</p>
                <p style="margin: 0 0 8px;"><strong>E-posta:</strong> {$email}</p>
                <p style="margin: 0 0 8px;"><strong>Telefon:</strong> {$phone}</p>
                <p style="margin: 0 0 16px;"><strong>Konu:</strong> {$subject}</p>
                <div style="padding: 16px; background: #f7f3eb; border-radius: 12px; white-space: pre-wrap;">{$body}</div>
                <p style="margin: 16px 0 0; color: #6b6560; font-size: 13px;">Yanıtlamak için yönetim panelindeki İletişim mesajları bölümünü kullanın.</p>
            </div>
            HTML;

        return [
            'to' => [$recipient],
            'subject' => "Yeni iletişim mesajı: {$subject}",
            'html' => $html,
            'text' => $text,
            'reply_to' => $this->message->email,
            'reply_to_name' => $this->message->name,
        ];
    }
}
