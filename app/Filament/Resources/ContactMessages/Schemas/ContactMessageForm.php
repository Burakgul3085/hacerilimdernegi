<?php

namespace App\Filament\Resources\ContactMessages\Schemas;

use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class ContactMessageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Gelen mesaj')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')->label('Ad')->disabled(),
                        TextInput::make('email')->label('E-posta')->disabled(),
                        TextInput::make('phone')->label('Telefon')->disabled(),
                        TextInput::make('subject')->label('Konu')->disabled(),
                        Textarea::make('message')->label('Mesaj')->disabled()->rows(6)->columnSpanFull(),
                        Toggle::make('is_read')->label('Okundu'),
                    ]),

                Section::make('Gönderilen yanıtlar')
                    ->schema([
                        Placeholder::make('replies_history')
                            ->label('')
                            ->content(function ($record): HtmlString {
                                if (! $record) {
                                    return new HtmlString('<p class="text-sm text-gray-500">Kayıt yok.</p>');
                                }

                                $replies = $record->replies()->with('user')->get();

                                if ($replies->isEmpty()) {
                                    return new HtmlString('<p class="text-sm text-gray-500">Henüz yanıt gönderilmedi. Üstteki “Yanıtla” ile cevap yazabilirsiniz.</p>');
                                }

                                $html = '<div class="space-y-4">';

                                foreach ($replies as $reply) {
                                    $author = e($reply->user?->name ?? 'Yönetici');
                                    $when = e(optional($reply->sent_at)->translatedFormat('d F Y H:i') ?? '');
                                    $body = nl2br(e($reply->body));
                                    $html .= <<<HTML
                                        <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-700">
                                            <p class="mb-2 text-xs text-gray-500">{$author} · {$when}</p>
                                            <div class="text-sm leading-relaxed">{$body}</div>
                                        </div>
                                        HTML;
                                }

                                $html .= '</div>';

                                return new HtmlString($html);
                            }),
                    ]),
            ]);
    }
}
