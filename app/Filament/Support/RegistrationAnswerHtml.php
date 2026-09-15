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
                '<p class="text-sm text-gray-500 dark:text-gray-400">Bu başvuruda ek form cevabı yok.</p>'
            );
        }

        $rows = '';

        foreach ($items as $index => $item) {
            $number = str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);
            $label = e($item['label']);
            $value = nl2br(e($item['value']));
            $border = $index === 0 ? '' : ' border-t border-gray-200 dark:border-white/10';

            $rows .= <<<HTML
                <div class="grid gap-3 py-5 sm:grid-cols-[3.5rem_minmax(0,1fr)]{$border}">
                    <div class="pt-0.5">
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-gray-900 text-[11px] font-semibold tracking-wide text-white dark:bg-white dark:text-gray-900">{$number}</span>
                    </div>
                    <div class="min-w-0">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-gray-500 dark:text-gray-400">{$label}</p>
                        <div class="mt-2 text-[15px] leading-relaxed text-gray-900 dark:text-gray-100">{$value}</div>
                    </div>
                </div>
                HTML;
        }

        return new HtmlString(
            '<div class="overflow-hidden rounded-2xl border border-gray-200 bg-white px-5 dark:border-white/10 dark:bg-white/5">'.$rows.'</div>'
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
