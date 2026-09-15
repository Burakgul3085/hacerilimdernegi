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
                Section::make('Başvuru dosyası')
                    ->description('Gelen katılım kaydı. Form cevaplarını buradan inceleyip durum güncelleyin veya yanıtlayın.')
                    ->columns(2)
                    ->schema([
                        Placeholder::make('program_title')
                            ->label('Program / faaliyet')
                            ->content(fn (?EventRegistration $record): string => $record?->subjectTitle() ?: '—'),
                        Placeholder::make('received_at')
                            ->label('Başvuru tarihi')
                            ->content(fn (?EventRegistration $record): string => $record?->created_at?->translatedFormat('d F Y, H:i') ?: '—'),
                        TextInput::make('name')->label('Ad soyad')->disabled(),
                        TextInput::make('email')->label('E-posta')->disabled(),
                        TextInput::make('phone')->label('Telefon')->disabled(),
                        Toggle::make('kvkk_accepted')->label('KVKK onayı')->disabled(),
                        Select::make('status')
                            ->label('Durum')
                            ->options(collect(ApplicationStatus::cases())->mapWithKeys(
                                fn (ApplicationStatus $status) => [$status->value => $status->label()],
                            )),
                    ]),

                Section::make('Form cevapları')
                    ->description('Başvuranın faaliyete özel sorulara verdiği yanıtlar.')
                    ->visible(fn (?EventRegistration $record): bool => (bool) $record?->hasStructuredAnswers())
                    ->schema([
                        Placeholder::make('form_answers')
                            ->label('')
                            ->content(function (?EventRegistration $record): HtmlString {
                                $items = $record?->answerItems() ?? [];

                                if ($items === []) {
                                    return new HtmlString('<p class="text-sm text-gray-500">Ek form cevabı yok.</p>');
                                }

                                $html = '<div class="space-y-3">';

                                foreach ($items as $index => $item) {
                                    $number = $index + 1;
                                    $label = e($item['label']);
                                    $value = nl2br(e($item['value']));
                                    $html .= <<<HTML
                                        <div class="rounded-xl border border-gray-200 bg-gray-50/80 p-4 dark:border-gray-700 dark:bg-gray-900/40">
                                            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-gray-500">Soru {$number}</p>
                                            <p class="mt-1 text-sm font-medium text-gray-900 dark:text-gray-100">{$label}</p>
                                            <div class="mt-3 border-t border-gray-200 pt-3 text-sm leading-relaxed text-gray-700 dark:border-gray-700 dark:text-gray-200">{$value}</div>
                                        </div>
                                        HTML;
                                }

                                $html .= '</div>';

                                return new HtmlString($html);
                            }),
                    ]),

                Section::make('Not')
                    ->description('Eski kayıtlarda veya sabit formlardan gelen serbest not.')
                    ->visible(fn (?EventRegistration $record): bool => filled($record?->notes) && ! $record?->hasStructuredAnswers())
                    ->schema([
                        Textarea::make('notes')->label('Not')->disabled()->rows(6)->columnSpanFull(),
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
