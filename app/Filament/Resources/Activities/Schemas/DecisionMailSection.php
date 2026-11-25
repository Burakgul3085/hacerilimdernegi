<?php

namespace App\Filament\Resources\Activities\Schemas;

use App\Enums\ApplicationStatus;
use App\Notifications\RegistrationDecisionMail;
use App\Support\DecisionMail;
use App\Support\MailRichText;
use App\Support\MailTemplate;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Throwable;

/**
 * Faaliyet formundaki onay, ret ve WhatsApp grubu ayarları.
 */
class DecisionMailSection
{
    /**
     * @var list<string>
     */
    private const STATE_KEYS = [
        'approval_mail_enabled', 'approval_mail_subject', 'approval_mail_body', 'approval_mail_button_label', 'approval_mail_button_url',
        'rejection_mail_enabled', 'rejection_mail_subject', 'rejection_mail_body', 'rejection_mail_button_label', 'rejection_mail_button_url',
        'whatsapp_group_enabled', 'whatsapp_group_url', 'whatsapp_group_in_approval', 'whatsapp_group_in_rejection',
    ];

    public static function tab(): Tab
    {
        return Tab::make('Başvuru e-postaları')
            ->icon(Heroicon::OutlinedEnvelope)
            ->schema([
                Text::make('Başvuru onaylandığında veya reddedildiğinde kişiye otomatik giden e-postalar. Her e-posta Site ayarları → Mailer sekmesindeki hitapla (örn. “Merhaba”) ve kişinin adıyla başlar, faaliyetin adı da üstte görünür; sizin yalnızca mesajı yazmanız yeterli.')
                    ->color('gray'),
                self::mailSection(ApplicationStatus::Approved),
                self::mailSection(ApplicationStatus::Rejected),
                self::whatsappSection(),
            ]);
    }

    private static function mailSection(ApplicationStatus $status): Section
    {
        $prefix = DecisionMail::prefix($status);
        $approved = $status === ApplicationStatus::Approved;
        $noun = $approved ? 'Onay' : 'Ret';

        return Section::make("{$noun} e-postası")
            ->description($approved ? 'Başvurusu onaylanan kişiye gider.' : 'Başvurusu kabul edilemeyen kişiye gider.')
            ->icon($approved ? Heroicon::OutlinedCheckBadge : Heroicon::OutlinedEnvelopeOpen)
            ->iconColor($approved ? 'success' : 'gray')
            ->collapsible()
            ->schema([
                Toggle::make("{$prefix}_mail_enabled")
                    ->label("{$noun} e-postası gönderilsin")
                    ->helperText($approved
                        ? 'Açıkken, başvuruyu “Onayla” ile onayladığınızda kişiye bu e-posta gider.'
                        : 'Açıkken, başvuruyu “Reddet” ile sonuçlandırdığınızda kişiye bu e-posta gider.')
                    ->default(true)
                    ->live(),

                TextInput::make("{$prefix}_mail_subject")
                    ->label('E-posta konusu')
                    ->maxLength(255)
                    ->placeholder(fn (Get $get): string => DecisionMail::defaultSubject($status, (string) $get('title')))
                    ->helperText('Boş bırakırsanız gri görünen konu kullanılır.')
                    ->afterStateHydrated(function (TextInput $component, ?string $state, Get $get) use ($status): void {
                        if (blank($state) && filled($get('title'))) {
                            $component->state(DecisionMail::defaultSubject($status, (string) $get('title')));
                        }
                    }),

                RichEditor::make("{$prefix}_mail_body")
                    ->label('Mesaj')
                    ->toolbarButtons([
                        ['bold', 'italic', 'underline', 'link'],
                        ['h3', 'bulletList', 'orderedList'],
                        ['undo', 'redo'],
                    ])
                    ->helperText('Selamlama ve imza otomatik eklenir. Mesajı boş bırakırsanız hazır metin gönderilir.')
                    ->afterStateHydrated(function (RichEditor $component, mixed $state) use ($status): void {
                        if (MailRichText::isBlank(is_array($state) || is_string($state) ? $state : null)) {
                            $component->state(DecisionMail::defaultBody($status));
                        }
                    })
                    ->hintAction(
                        Action::make("{$prefix}ResetBody")
                            ->label('Hazır metne dön')
                            ->icon(Heroicon::OutlinedArrowPath)
                            ->requiresConfirmation()
                            ->modalHeading('Hazır metne dönülsün mü?')
                            ->modalDescription('Yazdığınız mesaj silinip hazır metin yerleştirilecek. Kaydetmeden önce vazgeçebilirsiniz.')
                            ->modalSubmitActionLabel('Hazır metni yerleştir')
                            ->action(fn (Set $set) => $set("{$prefix}_mail_body", DecisionMail::defaultBody($status))),
                    ),

                Grid::make(2)->schema([
                    TextInput::make("{$prefix}_mail_button_label")
                        ->label('Ek buton yazısı')
                        ->maxLength(80)
                        ->placeholder($approved ? 'Örn. Konumu aç' : 'Örn. Diğer programlarımız')
                        ->requiredWith("{$prefix}_mail_button_url")
                        ->validationMessages(['required_with' => 'Buton adresini yazdıysanız buton yazısını da girin.']),
                    TextInput::make("{$prefix}_mail_button_url")
                        ->label('Ek buton adresi')
                        ->url()
                        ->maxLength(500)
                        ->placeholder('https://')
                        ->requiredWith("{$prefix}_mail_button_label")
                        ->validationMessages([
                            'required_with' => 'Buton yazısını girdiyseniz adresini de yazın.',
                            'url' => 'Geçerli bir adres girin (https:// ile başlamalı).',
                        ]),
                ]),

                Actions::make([
                    self::previewAction($status),
                    self::testAction($status),
                ])->alignEnd(),
            ]);
    }

    private static function whatsappSection(): Section
    {
        return Section::make('WhatsApp grubu')
            ->description('Grup bağlantısı e-postaların altında yeşil bir kart olarak görünür.')
            ->icon(Heroicon::OutlinedChatBubbleLeftRight)
            ->iconColor('success')
            ->collapsible()
            ->schema([
                Toggle::make('whatsapp_group_enabled')
                    ->label('WhatsApp grup bağlantısı e-postalarda görünsün')
                    ->helperText('Açıkken e-postanın altında yeşil “Gruba katıl” kartı yer alır. Kapattığınızda bundan sonraki e-postalarda görünmez.')
                    ->default(false)
                    ->live(),

                TextInput::make('whatsapp_group_url')
                    ->label('WhatsApp grup bağlantısı')
                    ->placeholder('https://chat.whatsapp.com/...')
                    ->prefixIcon(Heroicon::OutlinedLink)
                    ->maxLength(500)
                    ->helperText('WhatsApp’ta grubun “Davet bağlantısı” bölümünden kopyalayıp buraya yapıştırın.')
                    ->required(fn (Get $get): bool => (bool) $get('whatsapp_group_enabled'))
                    ->regex(DecisionMail::WHATSAPP_GROUP_PATTERN)
                    ->validationMessages([
                        'required' => 'Grup bağlantısı açıkken bağlantıyı yapıştırmanız gerekir.',
                        'regex' => 'Bu bir WhatsApp grup davet bağlantısı değil. Bağlantı https://chat.whatsapp.com/ ile başlamalı.',
                    ]),

                Grid::make(2)->schema([
                    Checkbox::make('whatsapp_group_in_approval')
                        ->label('Onay e-postasında göster')
                        ->default(true),
                    Checkbox::make('whatsapp_group_in_rejection')
                        ->label('Ret e-postasında göster')
                        ->default(true),
                ]),
            ]);
    }

    private static function previewAction(ApplicationStatus $status): Action
    {
        $noun = $status === ApplicationStatus::Approved ? 'Onay' : 'Ret';

        return Action::make(DecisionMail::prefix($status).'Preview')
            ->label('Önizle')
            ->icon(Heroicon::OutlinedEye)
            ->color('gray')
            ->modalHeading("{$noun} e-postası önizlemesi")
            ->modalDescription('Kaydetmediğiniz değişiklikler de önizlemede görünür.')
            ->modalWidth(Width::FourExtraLarge)
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Kapat')
            ->modalContent(function (Get $get) use ($status): View {
                $mail = self::composeFromForm($get, $status);

                return view('filament.decision-mail-preview', [
                    'subject' => $mail['subject'],
                    'html' => MailTemplate::forBrowser($mail['html']),
                    'recipient' => DecisionMail::SAMPLE_NAME,
                ]);
            });
    }

    private static function testAction(ApplicationStatus $status): Action
    {
        $noun = $status === ApplicationStatus::Approved ? 'onay' : 'ret';

        return Action::make(DecisionMail::prefix($status).'Test')
            ->label('Bana test gönder')
            ->icon(Heroicon::OutlinedPaperAirplane)
            ->color('gray')
            ->requiresConfirmation()
            ->modalIcon(Heroicon::OutlinedPaperAirplane)
            ->modalHeading('Test e-postası gönderilsin mi?')
            ->modalDescription(fn (): string => "Örnek {$noun} e-postası yalnızca sizin adresinize (".auth()->user()?->email.') gönderilecek. Başvuranlara bir şey gitmez.')
            ->modalSubmitActionLabel('Gönder')
            ->action(function (Get $get) use ($status): void {
                $mail = self::composeFromForm($get, $status);
                $mail['subject'] = '[Test] '.$mail['subject'];

                try {
                    NotificationFacade::route('mail', (string) auth()->user()?->email)
                        ->notifyNow(new RegistrationDecisionMail($mail));
                } catch (Throwable $exception) {
                    report($exception);

                    Notification::make()
                        ->title('Test e-postası gönderilemedi')
                        ->body($exception->getMessage())
                        ->danger()
                        ->send();

                    return;
                }

                Notification::make()
                    ->title('Test e-postası gönderildi')
                    ->body('Gelen kutunuzu kontrol edin.')
                    ->success()
                    ->send();
            });
    }

    /**
     * @return array{subject: string, html: string, text: string}
     */
    private static function composeFromForm(Get $get, ApplicationStatus $status): array
    {
        $state = [];

        foreach (self::STATE_KEYS as $key) {
            $state[$key] = $get($key);
        }

        $activityTitle = trim((string) $get('title')) ?: 'Faaliyet adı';
        $settings = DecisionMail::settingsFromState($state, $status, $activityTitle);

        return DecisionMail::compose($settings, $status, DecisionMail::SAMPLE_NAME, $activityTitle);
    }
}
