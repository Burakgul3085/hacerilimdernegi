<?php

namespace App\Filament\Resources\EventRegistrations\Schemas;

use App\Enums\ApplicationStatus;
use App\Models\EventRegistration;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class EventRegistrationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Gelen başvuru')
                    ->columns(2)
                    ->schema([
                        Placeholder::make('program_title')
                            ->label('Program')
                            ->content(fn (?EventRegistration $record): string => $record?->subjectTitle() ?: '—'),
                        TextInput::make('name')->label('Ad soyad')->disabled(),
                        TextInput::make('email')->label('E-posta')->disabled(),
                        TextInput::make('phone')->label('Telefon')->disabled(),
                        Textarea::make('notes')->label('Not / özet')->disabled()->rows(6)->columnSpanFull(),
                        Placeholder::make('form_answers')
                            ->label('Form cevapları')
                            ->columnSpanFull()
                            ->content(function (?EventRegistration $record): HtmlString {
                                $items = $record?->answerItems() ?? [];

                                if ($items === []) {
                                    return new HtmlString('<p class="text-sm text-gray-500">Ek form cevabı yok.</p>');
                                }

                                $html = '<dl class="space-y-3">';

                                foreach ($items as $item) {
                                    $label = e($item['label']);
                                    $value = nl2br(e($item['value']));
                                    $html .= <<<HTML
                                        <div class="rounded-xl border border-gray-200 p-3 dark:border-gray-700">
                                            <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">{$label}</dt>
                                            <dd class="mt-1 text-sm leading-relaxed">{$value}</dd>
                                        </div>
                                        HTML;
                                }

                                $html .= '</dl>';

                                return new HtmlString($html);
                            }),
                        Toggle::make('kvkk_accepted')->label('KVKK')->disabled(),
                        Select::make('status')
                            ->label('Durum')
                            ->options(collect(ApplicationStatus::cases())->mapWithKeys(
                                fn (ApplicationStatus $status) => [$status->value => $status->label()],
                            )),
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
