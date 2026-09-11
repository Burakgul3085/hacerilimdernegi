<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\AuthorizesByRole;
use App\Models\AuditLog;
use App\Models\Setting;
use App\Support\Icons;
use App\Support\InstagramMedia;
use App\Support\PhpMailerClient;
use App\Support\SiteSettings;
use App\Support\UploadRules;
use Closure;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ManageSettings extends Page
{
    use AuthorizesByRole;

    protected static ?string $title = 'Site ayarları';

    protected static ?string $navigationLabel = 'Site ayarları';

    protected static string|UnitEnum|null $navigationGroup = 'Kurum';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?int $navigationSort = 0;

    protected string $view = 'filament.pages.manage-settings';

    /**
     * @var array<string, mixed>
     */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return static::superAdminOnly();
    }

    public function mount(): void
    {
        $settings = SiteSettings::all();

        foreach (SiteSettings::LIST_KEYS as $key) {
            $settings[$key] = SiteSettings::list($key);
        }

        $settings['bank_details_are_demo'] = ($settings['bank_details_are_demo'] ?? '1') === '1';
        $settings['mailer_password'] = '';
        $settings['mailer_host_fixed'] = PhpMailerClient::HOST;
        $settings['mailer_port_fixed'] = (string) PhpMailerClient::PORT;
        $settings['mailer_encryption_fixed'] = 'TLS';

        $this->form->fill($settings);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Tabs::make()
                    ->persistTabInQueryString()
                    ->columnSpanFull()
                    ->tabs([
                        $this->identityTab(),
                        $this->navigationTab(),
                        $this->homepageTab(),
                        $this->pageContentTab(),
                        $this->contactTab(),
                        $this->broadcastTab(),
                        $this->donationTab(),
                        $this->mailerTab(),
                        $this->legalTab(),
                    ]),
            ]);
    }

    public function save(): void
    {
        $state = $this->form->getState();

        // Uygulama şifresi boş bırakılırsa mevcut şifreli değer korunur.
        if (! filled($state['mailer_password'] ?? null)) {
            unset($state['mailer_password']);
        } else {
            $state['mailer_password'] = PhpMailerClient::normalizeApplicationPassword((string) $state['mailer_password']);
        }

        foreach ($state as $key => $value) {
            if (in_array($key, SiteSettings::LIST_KEYS, true)) {
                $items = array_values($value ?? []);

                if ($key === 'nav_items') {
                    $items = array_map(function (mixed $item): mixed {
                        if (is_array($item) && isset($item['children']) && is_array($item['children'])) {
                            $item['children'] = array_values($item['children']);
                        }

                        return $item;
                    }, $items);
                }

                SiteSettings::put($key, json_encode($items, JSON_UNESCAPED_UNICODE));

                continue;
            }

            SiteSettings::put($key, is_bool($value) ? $value : (string) ($value ?? ''));
        }

        AuditLog::record('updated', Setting::class, null, 'Site ayarları');

        Notification::make()->title('Ayarlar kaydedildi')->success()->send();
    }

    private function identityTab(): Tab
    {
        return Tab::make('Kurum')
            ->icon(Heroicon::OutlinedBuildingLibrary)
            ->schema([
                Section::make('Kimlik')
                    ->columns(2)
                    ->schema([
                        TextInput::make('site_name')->label('Dernek adı')->required()->maxLength(255),
                        TextInput::make('tagline')->label('Kısa slogan')->maxLength(255),
                        Textarea::make('about_excerpt')->label('Kurum özeti')->rows(3)->columnSpanFull()
                            ->helperText('Ana sayfadaki "Hakkımızda" bloğunda ve arama motoru açıklamasında kullanılır.'),
                        TextInput::make('topbar_text')->label('Üst şerit metni')->maxLength(120)->columnSpanFull()
                            ->helperText('Sayfanın en üstündeki ince koyu şeritte görünür. Boş bırakılırsa şerit gizlenir.'),
                    ]),

                Section::make('Görsel kimlik')
                    ->columns(2)
                    ->schema([
                        FileUpload::make('logo')->label('Logo')->image()->disk('public')->directory('brand')
                            ->acceptedFileTypes(UploadRules::IMAGE_MIMES)->maxSize(UploadRules::MAX_IMAGE_KB),
                        FileUpload::make('favicon')->label('Favicon')->image()->disk('public')->directory('brand')
                            ->acceptedFileTypes(['image/png', 'image/x-icon', 'image/svg+xml', 'image/webp'])->maxSize(512),
                        ColorPicker::make('color_primary')->label('Ana renk'),
                        ColorPicker::make('color_gold')->label('Vurgu rengi'),
                    ]),
            ]);
    }

    private function navigationTab(): Tab
    {
        return Tab::make('Menü')
            ->icon(Heroicon::OutlinedBars3)
            ->schema([
                Section::make('Ana menü')
                    ->description('Sıralamayı sürükleyerek değiştirebilirsiniz. Üst başlıkların altına alt bağlantı eklenebilir; alt bağlantısı olan satırda üst yol boş bırakılabilir.')
                    ->schema([
                        Repeater::make('nav_items')
                            ->label('Menü bağlantıları')
                            ->addActionLabel('Bağlantı ekle')
                            ->itemLabel(fn (array $state): ?string => $state['label'] ?? null)
                            ->collapsed()
                            ->reorderableWithDragAndDrop()
                            ->schema([
                                TextInput::make('label')->label('Başlık')->required()->maxLength(60),
                                TextInput::make('url')->label('Bağlantı')->maxLength(255)
                                    ->helperText('Alt başlık varsa boş bırakılabilir. Site içi yol: /programlar'),
                                Repeater::make('children')
                                    ->label('Alt başlıklar')
                                    ->addActionLabel('Alt başlık ekle')
                                    ->itemLabel(fn (array $state): ?string => $state['label'] ?? null)
                                    ->collapsed()
                                    ->reorderableWithDragAndDrop()
                                    ->defaultItems(0)
                                    ->schema([
                                        TextInput::make('label')->label('Başlık')->required()->maxLength(60),
                                        TextInput::make('url')->label('Bağlantı')->required()->maxLength(255),
                                    ])
                                    ->columns(2)
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),
                    ]),

                Section::make('Menü butonu')
                    ->columns(2)
                    ->schema([
                        TextInput::make('nav_cta_label')->label('Buton yazısı')->maxLength(40)
                            ->helperText('Boş bırakılırsa koyu buton gizlenir.'),
                        TextInput::make('nav_cta_url')->label('Buton bağlantısı')->maxLength(255),
                    ]),
            ]);
    }

    private function homepageTab(): Tab
    {
        return Tab::make('Ana sayfa')
            ->icon(Heroicon::OutlinedHome)
            ->schema([
                Section::make('Giriş bölümü')
                    ->columns(2)
                    ->schema([
                        TextInput::make('hero_eyebrow')->label('Üst etiket')->maxLength(80),
                        FileUpload::make('hero_image')->label('Giriş görseli')->image()->disk('public')->directory('hero')
                            ->acceptedFileTypes(UploadRules::IMAGE_MIMES)->maxSize(UploadRules::MAX_IMAGE_KB),
                        TextInput::make('hero_title')->label('Başlık')->required()->maxLength(160)->columnSpanFull(),
                        Textarea::make('hero_text')->label('Açıklama')->rows(3)->columnSpanFull(),
                        TextInput::make('hero_primary_label')->label('Birincil buton yazısı')->maxLength(40),
                        TextInput::make('hero_primary_url')->label('Birincil buton bağlantısı')->maxLength(255),
                        TextInput::make('hero_secondary_label')->label('İkincil buton yazısı')->maxLength(40),
                        TextInput::make('hero_secondary_url')->label('İkincil buton bağlantısı')->maxLength(255),
                        Textarea::make('hero_quote')->label('Alıntı')->rows(2)->columnSpanFull(),
                        TextInput::make('hero_quote_author')->label('Alıntı sahibi')->maxLength(120)->columnSpanFull(),
                    ]),

                Section::make('Değerler şeridi')
                    ->description('Giriş bölümünün altındaki ikonlu kartlar.')
                    ->schema([
                        Repeater::make('value_pillars')
                            ->label('Değerler')
                            ->addActionLabel('Değer ekle')
                            ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                            ->collapsed()
                            ->reorderableWithDragAndDrop()
                            ->columns(3)
                            ->schema([
                                Select::make('icon')->label('İkon')->options(Icons::selectableOptions())->searchable()->default('sparkles')->required(),
                                TextInput::make('title')->label('Başlık')->required()->maxLength(40),
                                TextInput::make('text')->label('Açıklama')->maxLength(120),
                            ]),
                    ]),

                Section::make('Sayılarla dernek')
                    ->description('Ana sayfadaki ve Hakkımızda sayfasındaki istatistik kutuları.')
                    ->schema([
                        Repeater::make('stats')
                            ->label('İstatistikler')
                            ->addActionLabel('İstatistik ekle')
                            ->itemLabel(fn (array $state): ?string => $state['value'] ?? null)
                            ->collapsed()
                            ->reorderableWithDragAndDrop()
                            ->columns(2)
                            ->schema([
                                TextInput::make('value')->label('Değer')->required()->maxLength(20),
                                TextInput::make('label')->label('Açıklama')->required()->maxLength(60),
                            ]),
                    ]),

                Section::make('Çağrı bandı')
                    ->description('Ana sayfa ve Hakkımızda sayfasındaki koyu çağrı bloğu. Başlık boş bırakılırsa blok gizlenir.')
                    ->columns(2)
                    ->schema([
                        Select::make('cta_icon')->label('İkon')->options(Icons::selectableOptions())->searchable(),
                        TextInput::make('cta_title')->label('Başlık')->maxLength(120),
                        Textarea::make('cta_text')->label('Açıklama')->rows(2)->columnSpanFull(),
                        TextInput::make('cta_button_label')->label('Buton yazısı')->maxLength(40),
                        TextInput::make('cta_button_url')->label('Buton bağlantısı')->maxLength(255),
                    ]),

                Section::make('Bölüm başlıkları')
                    ->columns(2)
                    ->collapsed()
                    ->schema([
                        TextInput::make('home_programs_title')->label('Programlar başlığı')->maxLength(120),
                        TextInput::make('home_programs_text')->label('Programlar açıklaması')->maxLength(200),
                        TextInput::make('home_events_title')->label('Etkinlikler başlığı')->maxLength(120),
                        TextInput::make('home_events_text')->label('Etkinlikler açıklaması')->maxLength(200),
                        TextInput::make('home_posts_title')->label('Yazılar başlığı')->maxLength(120),
                        TextInput::make('home_posts_text')->label('Yazılar açıklaması')->maxLength(200),
                        TextInput::make('home_media_title')->label('Medya başlığı')->maxLength(120),
                        TextInput::make('home_media_text')->label('Medya açıklaması')->maxLength(200),
                        TextInput::make('home_location_eyebrow')->label('Konum üst etiketi')->maxLength(80),
                        TextInput::make('home_location_title')->label('Konum başlığı')->maxLength(120),
                        TextInput::make('home_location_button')->label('Harita butonu yazısı')->maxLength(40)->columnSpanFull(),
                    ]),
            ]);
    }

    private function pageContentTab(): Tab
    {
        return Tab::make('Sayfa metinleri')
            ->icon(Heroicon::OutlinedDocumentText)
            ->schema([
                Section::make('Hakkımızda')
                    ->columns(2)
                    ->schema([
                        FileUpload::make('about_image')->label('Kurum görseli')->image()->disk('public')->directory('about')
                            ->acceptedFileTypes(UploadRules::IMAGE_MIMES)->maxSize(UploadRules::MAX_IMAGE_KB),
                        Textarea::make('about_quote')->label('Öne çıkan alıntı')->rows(3),
                    ]),

                Section::make('Sayfa giriş yazıları')
                    ->description('Her sayfanın başlığının altında görünen kısa açıklamalar.')
                    ->columns(2)
                    ->schema([
                        Textarea::make('programs_intro')->label('Programlar')->rows(2),
                        Textarea::make('events_intro')->label('Etkinlikler')->rows(2),
                        Textarea::make('posts_intro')->label('Yazılar')->rows(2),
                        Textarea::make('media_intro')->label('Medya')->rows(2),
                        Textarea::make('membership_intro')->label('Üyelik')->rows(2),
                        Textarea::make('contact_intro')->label('İletişim')->rows(2),
                        Textarea::make('live_intro')->label('Vitrin')->rows(2),
                    ]),

                Section::make('Üyelik kartı')
                    ->columns(2)
                    ->schema([
                        TextInput::make('membership_card_title')->label('Başlık')->maxLength(80),
                        Textarea::make('membership_card_text')->label('Açıklama')->rows(2),
                    ]),

                Section::make('Alt bilgi')
                    ->columns(2)
                    ->schema([
                        TextInput::make('newsletter_title')->label('E-bülten başlığı')->maxLength(60),
                        TextInput::make('newsletter_text')->label('E-bülten açıklaması')->maxLength(160),
                        TextInput::make('footer_note')->label('Alt bilgi notu')->maxLength(200)->columnSpanFull(),
                    ]),

                Section::make('Yazılım künyesi')
                    ->description('Sayfanın en altındaki geliştirici satırı. İsim boş bırakılırsa satır tamamen gizlenir.')
                    ->columns(2)
                    ->collapsed()
                    ->schema([
                        TextInput::make('developer_label')->label('Künye etiketi')->maxLength(40)
                            ->placeholder('Tasarım ve yazılım'),
                        TextInput::make('developer_name')->label('Geliştirici adı')->maxLength(80),
                        TextInput::make('developer_url')->label('Profil bağlantısı')->url()->maxLength(255)
                            ->helperText('LinkedIn veya kişisel site adresi.'),
                        TextInput::make('developer_email')->label('Geliştirici e-postası')->email()->maxLength(180),
                    ]),
            ]);
    }

    private function contactTab(): Tab
    {
        return Tab::make('İletişim')
            ->icon(Heroicon::OutlinedMapPin)
            ->schema([
                Section::make('İletişim bilgileri')
                    ->columns(2)
                    ->schema([
                        Textarea::make('address')->label('Adres')->rows(2)->columnSpanFull(),
                        TextInput::make('phone')->label('Telefon')->tel()->maxLength(40)
                            ->helperText('Bu numara ana sayfadaki yeşil WhatsApp düğmesine, alt kısımdaki WhatsApp ikonuna ve iletişim sayfasındaki WhatsApp formuna gider.'),
                        TextInput::make('email')->label('E-posta')->email()->maxLength(180),
                        TextInput::make('domain')->label('Alan adı')->maxLength(255)
                            ->helperText('Yalnızca alan adı; "https://" olmadan yazın.'),
                        Textarea::make('map_embed')->label('Google Haritalar iframe kodu')->rows(3)->columnSpanFull(),
                    ]),

                Section::make('Sosyal medya')
                    ->description('Boş bırakılan kanallar sitede gösterilmez.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('telegram')->label('Telegram')->url()->maxLength(255),
                        TextInput::make('whatsapp')->label('WhatsApp')->url()->maxLength(255),
                        TextInput::make('twitter')->label('X (Twitter)')->url()->maxLength(255),
                        TextInput::make('instagram')->label('Instagram profili')->url()->maxLength(255)
                            ->helperText('Örnek: https://www.instagram.com/hacerilimkultur'),
                        TextInput::make('youtube')->label('YouTube')->url()->maxLength(255),
                        TextInput::make('facebook')->label('Facebook')->url()->maxLength(255),
                    ]),
            ]);
    }

    private function broadcastTab(): Tab
    {
        return Tab::make('Vitrin')
            ->icon(Heroicon::OutlinedCamera)
            ->schema([
                Section::make('Instagram vitrini')
                    ->description('Profil adresi Vitrin sayfasında görünür. Gönderi veya Reels linklerini sırayla ekleyin; kartlara tıklanınca Instagram gömülü görünümü açılır.')
                    ->schema([
                        Repeater::make('instagram_posts')
                            ->label('Paylaşımlar')
                            ->addActionLabel('Bağlantı ekle')
                            ->reorderableWithDragAndDrop()
                            ->schema([
                                TextInput::make('url')
                                    ->label('Gönderi veya Reels linki')
                                    ->url()
                                    ->required()
                                    ->maxLength(500)
                                    ->placeholder('https://www.instagram.com/p/... veya /reel/...')
                                    ->rules([
                                        function (): Closure {
                                            return function (string $attribute, mixed $value, Closure $fail): void {
                                                if (! is_string($value) || InstagramMedia::tryFrom($value) === null) {
                                                    $fail('Instagram gönderi veya Reels bağlantısı girin.');
                                                }
                                            };
                                        },
                                    ]),
                            ]),
                    ]),
            ]);
    }

    private function donationTab(): Tab
    {
        return Tab::make('Bağış')
            ->icon(Heroicon::OutlinedGift)
            ->schema([
                Section::make('Sayfa metni')
                    ->schema([
                        Textarea::make('donate_intro')->label('Giriş yazısı')->rows(3)
                            ->helperText('Bağış sayfasının başlığının altında ve ana sayfadaki bağış kartında görünür.'),
                    ]),

                Section::make('Hesap bilgileri')
                    ->description('Ziyaretçi bu bilgileri kopyalayıp banka havalesi veya EFT ile gönderir. Site üzerinden kart tahsilatı yoktur.')
                    ->columns(2)
                    ->schema([
                        Toggle::make('bank_details_are_demo')
                            ->label('Banka bilgileri demo')
                            ->helperText('Açıkken bağış sayfasında "ödeme yapmayınız" uyarısı gösterilir. Gerçek IBAN girildiğinde kapatın.')
                            ->columnSpanFull(),
                        TextInput::make('bank_account_name')->label('Hesap adı')->maxLength(180),
                        TextInput::make('bank_name')->label('Banka')->maxLength(120),
                        TextInput::make('bank_branch')->label('Şube')->maxLength(120)
                            ->helperText('Boş bırakılırsa sitede gösterilmez.'),
                        TextInput::make('iban')->label('IBAN')->maxLength(64)
                            ->helperText('Boşluksuz veya boşluklu yazılabilir; sitede dört haneli gruplar halinde gösterilir.'),
                        Textarea::make('donation_reference')->label('Havale açıklaması')->rows(2)->columnSpanFull()
                            ->helperText('Ziyaretçiye, dekont açıklamasına ne yazması gerektiğini söyler.'),
                        Textarea::make('donation_note')->label('Ek not')->rows(3)->columnSpanFull()
                            ->helperText('Hesap kartının altında görünür. Boş bırakılırsa gizlenir.'),
                    ]),

                Section::make('Bağışın kullanımı')
                    ->description('Bağış sayfasındaki “nereye gider” kartları. Tümü silinirse bölüm gizlenir.')
                    ->schema([
                        TextInput::make('donation_purposes_title')->label('Bölüm başlığı')->maxLength(80),
                        Repeater::make('donation_purposes')
                            ->label('Kalemler')
                            ->addActionLabel('Kalem ekle')
                            ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                            ->collapsed()
                            ->reorderableWithDragAndDrop()
                            ->defaultItems(0)
                            ->columns(3)
                            ->schema([
                                Select::make('icon')->label('İkon')->options(Icons::selectableOptions())->searchable()->default('gift')->required(),
                                TextInput::make('title')->label('Başlık')->required()->maxLength(40),
                                TextInput::make('text')->label('Açıklama')->maxLength(140),
                            ]),
                    ]),
            ]);
    }

    private function mailerTab(): Tab
    {
        return Tab::make('Mailer')
            ->icon(Heroicon::OutlinedEnvelope)
            ->schema([
                Section::make('SMTP / PHPMailer')
                    ->description('Yönetim paneli giriş doğrulama kodları PHPMailer ile Gmail üzerinden gönderilir. Sunucu, port ve şifreleme sabittir; yalnızca hesap bilgilerini girin.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('mailer_host_fixed')
                            ->label('SMTP sunucu')
                            ->default(PhpMailerClient::HOST)
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('mailer_port_fixed')
                            ->label('Port')
                            ->default((string) PhpMailerClient::PORT)
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('mailer_encryption_fixed')
                            ->label('Şifreleme')
                            ->default('TLS')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpanFull(),
                        TextInput::make('mailer_from_name')->label('Gönderen adı')->maxLength(120),
                        TextInput::make('mailer_username')->label('E-posta (SMTP kullanıcı adı)')->email()->maxLength(180)
                            ->helperText('Gönderimin yapılacağı Gmail hesabı.')
                            ->columnSpanFull(),
                        TextInput::make('mailer_password')->label('Uygulama şifresi')->password()->revealable()
                            ->maxLength(255)
                            ->helperText('Gmail hesap şifresi değil, Google Hesabı → Güvenlik → 2 Adımlı Doğrulama → Uygulama şifreleri ile üretilen 16 karakterlik şifre. Boş bırakırsanız kayıtlı şifre değişmez.')
                            ->columnSpanFull(),
                        TextInput::make('mailer_otp_to')->label('Doğrulama kodunun gideceği e-posta')->email()->maxLength(180)
                            ->helperText('Boş bırakılırsa kod, giriş yapan kullanıcının kendi e-posta adresine gider.')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    private function legalTab(): Tab
    {
        return Tab::make('Yasal metinler')
            ->icon(Heroicon::OutlinedShieldCheck)
            ->schema([
                Textarea::make('kvkk_text')->label('KVKK aydınlatma metni')->rows(10),
                Textarea::make('privacy_text')->label('Gizlilik politikası')->rows(8),
                Textarea::make('cookie_text')->label('Çerez politikası')->rows(6),
            ]);
    }
}
