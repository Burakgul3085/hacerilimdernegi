<?php

namespace App\Filament\Support;

use App\Models\EventRegistration;
use Illuminate\Support\HtmlString;

class RegistrationAnswerHtml
{
    /**
     * @param  list<array{key?: string, label: string, value: string}>  $items
     */
    public static function answers(array $items): HtmlString
    {
        if ($items === []) {
            return new HtmlString(
                '<p style="margin:0;font-size:14px;color:#6b7280;">Bu başvuruda ek form cevabı yok.</p>'
            );
        }

        $rows = '';

        foreach ($items as $index => $item) {
            $number = str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);
            $label = e($item['label']);
            $value = nl2br(e($item['value']));
            $margin = $index === array_key_last($items) ? '0' : '0 0 12px';

            $rows .= <<<HTML
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:{$margin};border:1px solid #e5e7eb;border-radius:14px;background:#ffffff;border-collapse:separate;overflow:hidden;">
                    <tr>
                        <td width="56" valign="top" style="width:56px;padding:16px 0 16px 16px;">
                            <div style="width:36px;height:36px;border-radius:999px;background:#161513;color:#ffffff;font-size:12px;font-weight:700;letter-spacing:0.04em;line-height:36px;text-align:center;">{$number}</div>
                        </td>
                        <td valign="top" style="padding:16px 18px 16px 12px;">
                            <p style="margin:0 0 8px;font-size:11px;font-weight:700;letter-spacing:0.14em;text-transform:uppercase;color:#8a7a62;">{$label}</p>
                            <div style="font-size:15px;line-height:1.7;color:#161513;word-break:break-word;">{$value}</div>
                        </td>
                    </tr>
                </table>
                HTML;
        }

        return new HtmlString(
            '<div style="display:block;">'.$rows.'</div>'
        );
    }

    public static function replies(?EventRegistration $record): HtmlString
    {
        if (! $record) {
            return new HtmlString('<p class="text-sm text-gray-500">Kayıt yok.</p>');
        }

        $replies = $record->replies()->with('user')->get();

        if ($replies->isEmpty()) {
            return new HtmlString(
                '<div class="rounded-2xl border border-dashed border-gray-300 px-5 py-8 text-center dark:border-white/15">'
                .'<p class="text-sm text-gray-500 dark:text-gray-400">Henüz yanıt gönderilmedi.</p>'
                .'<p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Üstteki “Yanıtla” ile e-posta gönderebilirsiniz.</p>'
                .'</div>'
            );
        }

        $html = '<div class="space-y-3">';

        foreach ($replies as $reply) {
            $author = e($reply->user?->name ?? 'Yönetici');
            $when = e(optional($reply->sent_at)->translatedFormat('d F Y H:i') ?? '');
            $body = nl2br(e($reply->body));
            $html .= <<<HTML
                <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-white/10 dark:bg-white/5">
                    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-100 pb-3 dark:border-white/10">
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{$author}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{$when}</p>
                    </div>
                    <div class="mt-3 text-sm leading-relaxed text-gray-700 dark:text-gray-200">{$body}</div>
                </div>
                HTML;
        }

        return new HtmlString($html.'</div>');
    }

    public static function contactLine(?EventRegistration $record): HtmlString
    {
        if (! $record) {
            return new HtmlString('—');
        }

        $parts = [];
        $email = e($record->email);
        $parts[] = '<a href="mailto:'.$email.'" class="text-primary-600 underline decoration-primary-600/30 underline-offset-2 hover:decoration-primary-600 dark:text-primary-400">'.$email.'</a>';

        if (filled($record->phone)) {
            $phone = e($record->phone);
            $parts[] = '<a href="tel:'.$phone.'" class="text-gray-700 underline decoration-gray-300 underline-offset-2 hover:decoration-gray-500 dark:text-gray-200">'.$phone.'</a>';
        }

        return new HtmlString(implode('<span class="mx-2 text-gray-300 dark:text-gray-600">·</span>', $parts));
    }
}
