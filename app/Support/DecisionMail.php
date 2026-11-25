<?php

namespace App\Support;

use App\Enums\ApplicationStatus;
use App\Models\Activity;
use InvalidArgumentException;

/**
 * Faaliyete özel onay ve ret e-postalarının ayarlarını, hazır metinlerini
 * ve kurumsal şablona dökülmüş son halini tek yerden üretir.
 */
final class DecisionMail
{
    public const WHATSAPP_GROUP_PATTERN = '/^https:\/\/chat\.whatsapp\.com\/[A-Za-z0-9_-]{10,}\/?(\?.*)?$/';

    public const SAMPLE_NAME = 'Ayşe Yılmaz';

    /**
     * @return 'approval'|'rejection'
     */
    public static function prefix(ApplicationStatus $status): string
    {
        return match ($status) {
            ApplicationStatus::Approved => 'approval',
            ApplicationStatus::Rejected => 'rejection',
            ApplicationStatus::Pending => throw new InvalidArgumentException('Bekleyen başvuru için karar e-postası yoktur.'),
        };
    }

    public static function defaultSubject(ApplicationStatus $status, string $activityTitle): string
    {
        $suffix = $status === ApplicationStatus::Approved ? 'Başvurunuz onaylandı' : 'Başvurunuz hakkında';
        $activityTitle = trim($activityTitle);

        return $activityTitle === '' ? $suffix : "{$activityTitle} · {$suffix}";
    }

    public static function defaultBody(ApplicationStatus $status): string
    {
        if ($status === ApplicationStatus::Approved) {
            return '<p>Programımıza gösterdiğiniz ilgi için teşekkür ederiz. Başvurunuz değerlendirilmiş ve <strong>katılımınız onaylanmıştır</strong>.</p>'
                .'<p>Sizi aramızda görmekten mutluluk duyacağız. Ders gün ve saatleriyle ilgili bilgilendirmeler ayrıca paylaşılacaktır.</p>';
        }

        return '<p>Programımıza gösterdiğiniz ilgi için içtenlikle teşekkür ederiz. Kontenjanımız dolduğu için bu dönem başvurunuzu maalesef kabul edemedik.</p>'
            .'<p>Yeni programlarımızda sizi de aramızda görmeyi çok isteriz. Duyurularımızı takip etmeyi unutmayın.</p>';
    }

    public static function isValidWhatsappGroupUrl(?string $url): bool
    {
        return preg_match(self::WHATSAPP_GROUP_PATTERN, trim((string) $url)) === 1;
    }

    /**
     * Kayıtlı faaliyet ayarlarından e-posta ayarlarını okur. Faaliyet yoksa hazır metin kullanılır.
     *
     * @return array{enabled: bool, subject: string, body: string, button_label: ?string, button_url: ?string, whatsapp_url: ?string}
     */
    public static function settingsFor(?Activity $activity, ApplicationStatus $status): array
    {
        if ($activity === null) {
            return self::settingsFromState([], $status, '');
        }

        return self::settingsFromState($activity->only([
            'approval_mail_enabled', 'approval_mail_subject', 'approval_mail_body', 'approval_mail_button_label', 'approval_mail_button_url',
            'rejection_mail_enabled', 'rejection_mail_subject', 'rejection_mail_body', 'rejection_mail_button_label', 'rejection_mail_button_url',
            'whatsapp_group_enabled', 'whatsapp_group_url', 'whatsapp_group_in_approval', 'whatsapp_group_in_rejection',
        ]), $status, (string) $activity->title);
    }

    /**
     * Panel formundaki (henüz kaydedilmemiş olabilecek) değerlerden e-posta ayarlarını çıkarır.
     *
     * @param  array<string, mixed>  $state
     * @return array{enabled: bool, subject: string, body: string, button_label: ?string, button_url: ?string, whatsapp_url: ?string}
     */
    public static function settingsFromState(array $state, ApplicationStatus $status, string $activityTitle): array
    {
        $prefix = self::prefix($status);
        $subject = trim((string) ($state["{$prefix}_mail_subject"] ?? ''));
        $body = $state["{$prefix}_mail_body"] ?? null;
        $buttonLabel = trim((string) ($state["{$prefix}_mail_button_label"] ?? ''));
        $buttonUrl = trim((string) ($state["{$prefix}_mail_button_url"] ?? ''));
        $whatsappUrl = trim((string) ($state['whatsapp_group_url'] ?? ''));
        $showWhatsapp = (bool) ($state['whatsapp_group_enabled'] ?? false)
            && (bool) ($state["whatsapp_group_in_{$prefix}"] ?? true)
            && self::isValidWhatsappGroupUrl($whatsappUrl);

        return [
            'enabled' => (bool) ($state["{$prefix}_mail_enabled"] ?? true),
            'subject' => $subject !== '' ? $subject : self::defaultSubject($status, $activityTitle),
            'body' => MailRichText::isBlank($body) ? self::defaultBody($status) : MailRichText::toEmailHtml($body),
            'button_label' => $buttonLabel !== '' && preg_match('/^https?:\/\//i', $buttonUrl) === 1 ? $buttonLabel : null,
            'button_url' => $buttonLabel !== '' && preg_match('/^https?:\/\//i', $buttonUrl) === 1 ? $buttonUrl : null,
            'whatsapp_url' => $showWhatsapp ? $whatsappUrl : null,
        ];
    }

    /**
     * @param  array{enabled: bool, subject: string, body: string, button_label: ?string, button_url: ?string, whatsapp_url: ?string}  $settings
     * @return array{subject: string, html: string, text: string}
     */
    public static function compose(array $settings, ApplicationStatus $status, string $recipientName, string $activityTitle): array
    {
        $approved = $status === ApplicationStatus::Approved;
        $title = $approved ? 'Başvurunuz onaylandı' : 'Başvurunuz hakkında';
        $bodyHtml = MailRichText::toEmailHtml($settings['body']);

        $buttons = [];

        if ($settings['button_label'] !== null && $settings['button_url'] !== null) {
            $buttons[] = ['label' => $settings['button_label'], 'url' => $settings['button_url']];
        }

        $html = MailTemplate::render([
            'title' => $title,
            'preheader' => $approved
                ? "«{$activityTitle}» için katılımınız onaylandı."
                : "«{$activityTitle}» başvurunuzla ilgili bilgilendirme.",
            'eyebrow' => $approved ? 'Katılım onayı' : 'Başvuru sonucu',
            'greeting' => MailSalutation::greetingHtml($recipientName),
            'subject_card' => [
                'label' => 'Faaliyet',
                'title' => $activityTitle,
                'badge' => $approved ? 'Onaylandı' : 'Değerlendirildi',
                'tone' => $approved ? 'success' : 'neutral',
            ],
            'body' => $bodyHtml,
            'buttons' => $buttons,
            'whatsapp' => $settings['whatsapp_url'] !== null ? [
                'url' => $settings['whatsapp_url'],
                'title' => 'WhatsApp grubumuza katılın',
                'text' => $approved
                    ? 'Program duyuruları, ders saatleri ve hatırlatmalar bu grupta paylaşılır.'
                    : 'Yeni programlarımızdan ve duyurularımızdan ilk siz haberdar olun.',
            ] : null,
            'closing' => MailSalutation::closingHtml(),
        ]);

        $textParts = [
            MailSalutation::greetingText($recipientName),
            "{$title} — {$activityTitle}",
            MailRichText::toPlainText($bodyHtml),
        ];

        if ($buttons !== []) {
            $textParts[] = $buttons[0]['label'].': '.$buttons[0]['url'];
        }

        if ($settings['whatsapp_url'] !== null) {
            $textParts[] = 'WhatsApp grubumuz: '.$settings['whatsapp_url'];
        }

        $textParts[] = MailSalutation::closingText();

        return [
            'subject' => $settings['subject'],
            'html' => $html,
            'text' => implode("\n\n", $textParts),
        ];
    }
}
