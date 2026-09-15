<?php

namespace App\Filament\Resources\EventRegistrations\Schemas;

use App\Enums\ApplicationStatus;
use App\Filament\Support\RegistrationAnswerHtml;
use App\Models\EventRegistration;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Html;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EventRegistrationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Başvuran')
                    ->description('İletişim bilgileri ve KVKK onayı.')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('name')
                                ->label('Ad soyad')
                                ->weight('font-semibold')
                                ->size('lg'),
                            TextEntry::make('received_at')
                                ->label('Başvuru tarihi')
                                ->state(fn (?EventRegistration $record): string => $record?->created_at?->translatedFormat('d F Y, H:i') ?: '—'),
                            TextEntry::make('contact')
                                ->label('İletişim')
                                ->state(fn (?EventRegistration $record) => RegistrationAnswerHtml::contactLine($record))
                                ->html()
                                ->columnSpanFull(),
                            TextEntry::make('kvkk_accepted')
                                ->label('KVKK')
                                ->badge()
                                ->formatStateUsing(fn (?bool $state): string => $state ? 'Onaylandı' : 'Yok')
                                ->color(fn (?bool $state): string => $state ? 'success' : 'danger'),
                        ]),
                    ]),

                Section::make('Program')
                    ->description('Başvurunun bağlı olduğu faaliyet veya program.')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('program_title')
                                ->label('Program / faaliyet')
                                ->state(fn (?EventRegistration $record): string => $record?->subjectTitle() ?: '—')
                                ->weight('font-medium')
                                ->columnSpanFull(),
                            Select::make('status')
                                ->label('Durum')
                                ->options(collect(ApplicationStatus::cases())->mapWithKeys(
                                    fn (ApplicationStatus $status): array => [$status->value => $status->label()],
                                ))
                                ->native(false)
                                ->helperText('Başvuru durumunu buradan güncelleyin.'),
                            TextEntry::make('reply_status')
                                ->label('Yanıt durumu')
                                ->badge()
                                ->state(fn (?EventRegistration $record): string => $record?->replied_at ? 'Yanıtlandı' : 'Bekliyor')
                                ->color(fn (?EventRegistration $record): string => $record?->replied_at ? 'success' : 'warning'),
                        ]),
                    ]),

                Section::make('Form cevapları')
                    ->description('Faaliyete özel sorulara verilen yanıtlar.')
                    ->visible(fn (?EventRegistration $record): bool => (bool) $record?->hasStructuredAnswers())
                    ->schema([
                        Html::make(fn (?EventRegistration $record) => RegistrationAnswerHtml::answers($record?->answerItems() ?? [])),
                    ]),

                Section::make('Not')
                    ->description('Sabit formlardan gelen serbest not.')
                    ->visible(fn (?EventRegistration $record): bool => filled($record?->notes) && ! $record?->hasStructuredAnswers())
                    ->schema([
                        Textarea::make('notes')
                            ->label('Not metni')
                            ->disabled()
                            ->rows(5)
                            ->columnSpanFull(),
                    ]),

                Section::make('Yazışma')
                    ->description('Panele gönderilen yanıtlar.')
                    ->collapsed(fn (?EventRegistration $record): bool => blank($record?->replied_at))
                    ->schema([
                        Html::make(fn (?EventRegistration $record) => RegistrationAnswerHtml::replies($record)),
                    ]),
            ]);
    }
}
