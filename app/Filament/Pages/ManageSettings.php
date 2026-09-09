<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\AuthorizesByRole;
use App\Support\SiteSettings;
use App\Support\UploadRules;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use UnitEnum;

class ManageSettings extends Page
{
    use AuthorizesByRole;
    use WithFileUploads;

    protected static ?string $title = 'Site ayarları';

    protected static ?string $navigationLabel = 'Site ayarları';

    protected static string|UnitEnum|null $navigationGroup = 'Kurum';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?int $navigationSort = 0;

    protected string $view = 'filament.pages.manage-settings';

    public string $site_name = '';

    public string $tagline = '';

    public string $about_excerpt = '';

    public string $address = '';

    public string $phone = '';

    public string $email = '';

    public string $domain = '';

    public string $map_embed = '';

    public string $facebook = '';

    public string $instagram = '';

    public string $youtube = '';

    public string $telegram = '';

    public string $whatsapp = '';

    public string $twitter = '';

    public string $live_youtube_url = '';

    public string $live_instagram_url = '';

    public bool $live_is_active = false;

    public string $iban = '';

    public string $bank_account_name = '';

    public string $bank_name = '';

    public bool $bank_details_are_demo = true;

    public string $donation_note = '';

    public string $color_primary = '#161513';

    public string $color_gold = '#8A7A62';

    public string $kvkk_text = '';

    public string $privacy_text = '';

    public string $cookie_text = '';

    public mixed $logo = null;

    public mixed $favicon = null;

    public static function canAccess(): bool
    {
        return static::superAdminOnly();
    }

    public function mount(): void
    {
        $settings = SiteSettings::all();

        $this->site_name = (string) $settings['site_name'];
        $this->tagline = (string) $settings['tagline'];
        $this->about_excerpt = (string) $settings['about_excerpt'];
        $this->address = (string) $settings['address'];
        $this->phone = (string) $settings['phone'];
        $this->email = (string) $settings['email'];
        $this->domain = (string) $settings['domain'];
        $this->map_embed = (string) $settings['map_embed'];
        $this->facebook = (string) $settings['facebook'];
        $this->instagram = (string) $settings['instagram'];
        $this->youtube = (string) $settings['youtube'];
        $this->telegram = (string) ($settings['telegram'] ?? '');
        $this->whatsapp = (string) ($settings['whatsapp'] ?? '');
        $this->twitter = (string) ($settings['twitter'] ?? '');
        $this->live_youtube_url = (string) $settings['live_youtube_url'];
        $this->live_instagram_url = (string) $settings['live_instagram_url'];
        $this->live_is_active = (string) $settings['live_is_active'] === '1';
        $this->iban = (string) $settings['iban'];
        $this->bank_account_name = (string) $settings['bank_account_name'];
        $this->bank_name = (string) $settings['bank_name'];
        $this->bank_details_are_demo = (string) $settings['bank_details_are_demo'] === '1';
        $this->donation_note = (string) $settings['donation_note'];
        $this->color_primary = (string) $settings['color_primary'];
        $this->color_gold = (string) $settings['color_gold'];
        $this->kvkk_text = (string) $settings['kvkk_text'];
        $this->privacy_text = (string) $settings['privacy_text'];
        $this->cookie_text = (string) $settings['cookie_text'];
    }

    public function save(): void
    {
        $this->validate([
            'site_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email'],
            'domain' => ['nullable', 'string', 'max:255'],
            'iban' => ['nullable', 'string', 'max:64'],
            'logo' => ['nullable', 'image', 'mimes:'.implode(',', UploadRules::IMAGE_EXTENSIONS), 'max:'.UploadRules::MAX_IMAGE_KB],
            'favicon' => ['nullable', 'image', 'mimes:png,ico,svg,webp', 'max:512'],
        ]);

        $fields = [
            'site_name', 'tagline', 'about_excerpt', 'address', 'phone', 'email', 'domain', 'map_embed',
            'facebook', 'instagram', 'youtube', 'telegram', 'whatsapp', 'twitter', 'live_youtube_url', 'live_instagram_url',
            'iban', 'bank_account_name', 'bank_name', 'donation_note',
            'color_primary', 'color_gold', 'kvkk_text', 'privacy_text', 'cookie_text',
        ];

        foreach ($fields as $field) {
            SiteSettings::put($field, $this->{$field});
        }

        SiteSettings::put('live_is_active', $this->live_is_active);
        SiteSettings::put('bank_details_are_demo', $this->bank_details_are_demo);

        if ($this->logo instanceof TemporaryUploadedFile) {
            SiteSettings::put('logo', $this->logo->store('brand', 'public'));
        }

        if ($this->favicon instanceof TemporaryUploadedFile) {
            SiteSettings::put('favicon', $this->favicon->store('brand', 'public'));
        }

        Notification::make()->title('Ayarlar kaydedildi')->success()->send();
    }
}
