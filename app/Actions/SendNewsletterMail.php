<?php

namespace App\Actions;

use App\Models\NewsletterSubscriber;
use App\Notifications\NewsletterMailSent;
use App\Support\PhpMailerClient;
use Illuminate\Support\Collection;
use RuntimeException;
use Throwable;

/**
 * Yönetim panelinden seçilen e-bülten abonelerine kurumsal şablonla mail gönderir.
 */
class SendNewsletterMail
{
    public function __construct(private PhpMailerClient $mailer) {}

    /**
     * @param  iterable<int, NewsletterSubscriber>  $subscribers
     * @return array{sent: int, failed: int}
     */
    public function handle(iterable $subscribers, string $subject, string $body): array
    {
        $subject = trim($subject);
        $body = trim($body);

        if ($subject === '' || $body === '') {
            throw new RuntimeException('Konu ve metin zorunludur.');
        }

        if (! $this->mailer->isConfigured()) {
            throw new RuntimeException('Mailer ayarları eksik. Site ayarlarından e-posta ve uygulama şifresini kaydedin.');
        }

        /** @var Collection<int, NewsletterSubscriber> $recipients */
        $recipients = Collection::make($subscribers)
            ->unique(fn (NewsletterSubscriber $subscriber): int => $subscriber->id)
            ->values();

        if ($recipients->isEmpty()) {
            throw new RuntimeException('Gönderilecek abone yok.');
        }

        $sent = 0;
        $failed = 0;

        foreach ($recipients as $subscriber) {
            try {
                $subscriber->notify(new NewsletterMailSent($subject, $body));
                $sent++;
            } catch (Throwable $exception) {
                report($exception);
                $failed++;
            }
        }

        return [
            'sent' => $sent,
            'failed' => $failed,
        ];
    }
}
