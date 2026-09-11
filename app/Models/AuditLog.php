<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use UnitEnum;

class AuditLog extends Model
{
    /**
     * @var list<string>
     */
    private const HIDDEN_KEYS = [
        'id',
        'password',
        'remember_token',
        'app_authentication_secret',
        'app_authentication_recovery_codes',
        'mailer_password',
        'email_verified_at',
        'is_read',
        'kvkk_accepted',
        'ip_address',
        'user_agent',
        'updated_at',
        'created_at',
    ];

    /**
     * @var array<string, string>
     */
    private const FIELD_LABELS = [
        'title' => 'Başlık',
        'name' => 'Ad',
        'slug' => 'Bağlantı',
        'subtitle' => 'Alt başlık',
        'excerpt' => 'Özet',
        'body' => 'İçerik',
        'description' => 'Açıklama',
        'image' => 'Görsel',
        'document' => 'PDF',
        'board_members' => 'Yönetim kadrosu',
        'president_name' => 'Başkan adı',
        'president_title' => 'Başkan ünvanı',
        'vision' => 'Vizyon',
        'mission' => 'Misyon',
        'cover' => 'Kapak',
        'gallery' => 'Galeri',
        'is_published' => 'Yayında',
        'published_at' => 'Yayın tarihi',
        'type' => 'Tür',
        'location' => 'Yer',
        'author_name' => 'Yazar',
        'author_id' => 'Yazar',
        'category_id' => 'Kategori',
        'email' => 'E-posta',
        'role' => 'Rol',
        'caption' => 'Açıklama',
        'path' => 'Dosya',
        'external_url' => 'Bağlantı',
        'instructor' => 'Eğitmen',
        'starts_at' => 'Başlangıç',
        'ends_at' => 'Bitiş',
        'capacity' => 'Kapasite',
        'registration_open' => 'Kayıt açık',
        'key' => 'Ayar',
        'value' => 'Değer',
        'status' => 'Durum',
        'phone' => 'Telefon',
        'city' => 'Şehir',
        'message' => 'Mesaj',
        'subject' => 'Konu',
        'notes' => 'Not',
        'replied_at' => 'Yanıt tarihi',
        'event_id' => 'Etkinlik',
        'media_album_id' => 'Albüm',
        'seo_title' => 'Arama başlığı',
        'seo_description' => 'Arama açıklaması',
        'featured_quote' => 'Alıntı',
        'source_url' => 'Kaynak bağlantısı',
        'source_label' => 'Kaynak',
        'sort_order' => 'Sıra',
    ];

    protected $fillable = [
        'user_id', 'action', 'model_type', 'model_id', 'ip_address', 'user_agent', 'properties',
    ];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @param  array<string, mixed>  $changes
     */
    public static function record(string $action, ?string $modelType, mixed $modelId, ?string $label, array $changes = []): void
    {
        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            return;
        }

        if (! auth()->check()) {
            return;
        }

        static::query()->create([
            'user_id' => auth()->id(),
            'action' => $action,
            'model_type' => $modelType,
            'model_id' => is_numeric($modelId) ? (int) $modelId : null,
            'ip_address' => request()?->ip(),
            'user_agent' => substr((string) request()?->userAgent(), 0, 255),
            'properties' => [
                'label' => $label,
                'changes' => self::sanitizeChanges($changes),
            ],
        ]);
    }

    public static function recordFromModel(Model $model, string $action): void
    {
        $changes = $action === 'updated'
            ? $model->getChanges()
            : $model->getAttributes();

        $changes = self::sanitizeChanges($changes);

        if ($action === 'updated' && $changes === []) {
            return;
        }

        self::record(
            $action,
            $model::class,
            $model->getKey(),
            self::labelForModel($model),
            $changes,
        );
    }

    public function actorName(): string
    {
        return $this->user?->name ?: 'Sistem';
    }

    public function actionLabel(): string
    {
        return match ($this->action) {
            'created' => 'Ekledi',
            'updated' => 'Güncelledi',
            'deleted' => 'Sildi',
            default => (string) $this->action,
        };
    }

    public function typeLabel(): string
    {
        return match ($this->model_type) {
            Post::class => 'Yazı / duyuru',
            MediaAlbum::class => 'Albüm',
            MediaItem::class => 'Medya öğesi',
            Program::class => 'Program',
            Event::class => 'Etkinlik',
            Page::class => 'Sayfa',
            Category::class => 'Kategori',
            User::class => 'Kullanıcı',
            Setting::class => 'Site ayarı',
            MembershipApplication::class => 'Üyelik başvurusu',
            EventRegistration::class => 'Etkinlik kaydı',
            ContactMessage::class => 'İletişim mesajı',
            NewsletterSubscriber::class => 'E-bülten',
            default => filled($this->model_type) ? class_basename($this->model_type) : 'Kayıt',
        };
    }

    public function recordLabel(): string
    {
        $properties = $this->properties ?? [];

        if (is_string($properties['label'] ?? null) && filled($properties['label'])) {
            return $properties['label'];
        }

        foreach (['title', 'name', 'email', 'key'] as $key) {
            $value = $properties[$key] ?? null;

            if (is_string($value) && filled($value)) {
                return $value;
            }
        }

        return $this->typeLabel();
    }

    public function summary(): string
    {
        $who = $this->actorName();
        $label = $this->recordLabel();
        $verb = match ($this->action) {
            'created' => 'ekledi',
            'updated' => 'güncelledi',
            'deleted' => 'sildi',
            default => (string) $this->action,
        };

        if ($this->model_type === Setting::class) {
            return $who.' site ayarlarını '.$verb.'.';
        }

        $noun = match ($this->model_type) {
            Post::class => 'yazısını',
            MediaAlbum::class => 'albümünü',
            MediaItem::class => 'medya öğesini',
            Program::class => 'programını',
            Event::class => 'etkinliğini',
            Page::class => 'sayfasını',
            Category::class => 'kategorisini',
            User::class => 'kullanıcısını',
            MembershipApplication::class => 'üyelik başvurusunu',
            EventRegistration::class => 'etkinlik kaydını',
            ContactMessage::class => 'iletişim mesajını',
            NewsletterSubscriber::class => 'e-bülten kaydını',
            default => 'kaydını',
        };

        $sentence = $who.' «'.$label.'» '.$noun.' '.$verb;

        if ($this->action !== 'updated') {
            return $sentence.'.';
        }

        $changedLabels = [];

        foreach (array_keys($this->visibleChanges()) as $key) {
            if (! is_string($key)) {
                continue;
            }

            $changedLabels[] = self::fieldLabel($key);

            if (count($changedLabels) === 4) {
                break;
            }
        }

        if ($changedLabels === []) {
            return $sentence.'.';
        }

        return $sentence.' ('.implode(', ', $changedLabels).').';
    }

    public function changeSummaryText(): string
    {
        $changes = $this->visibleChanges();

        if ($changes === []) {
            return match ($this->action) {
                'created' => 'Yeni kayıt eklendi.',
                'deleted' => 'Kayıt silindi.',
                default => 'Kayıt güncellendi.',
            };
        }

        $lines = [];

        foreach ($changes as $key => $value) {
            if (! is_string($key)) {
                continue;
            }

            $lines[] = self::fieldLabel($key).': '.$this->stringifyChange($key, $value);
        }

        return implode("\n", $lines);
    }

    /**
     * @return array<string, mixed>
     */
    public function visibleChanges(): array
    {
        $properties = $this->properties ?? [];

        if (isset($properties['changes']) && is_array($properties['changes'])) {
            return self::sanitizeChanges($properties['changes']);
        }

        unset($properties['label'], $properties['changes']);

        return self::sanitizeChanges(is_array($properties) ? $properties : []);
    }

    public static function labelForModel(Model $model): string
    {
        foreach (['title', 'name', 'email', 'key'] as $attribute) {
            $value = $model->getAttribute($attribute);

            if (is_string($value) && filled($value)) {
                return $value;
            }
        }

        return class_basename($model).' #'.$model->getKey();
    }

    public static function fieldLabel(string $key): string
    {
        return self::FIELD_LABELS[$key] ?? Str::of($key)->replace('_', ' ')->ucfirst()->toString();
    }

    /**
     * @param  array<string, mixed>  $changes
     * @return array<string, mixed>
     */
    public static function sanitizeChanges(array $changes): array
    {
        $clean = [];

        foreach ($changes as $key => $value) {
            if (! is_string($key) || in_array($key, self::HIDDEN_KEYS, true)) {
                continue;
            }

            if (is_string($value)) {
                $value = Str::limit(trim(strip_tags($value)), 200);
            }

            $clean[$key] = $value;
        }

        return $clean;
    }

    private function stringifyChange(string $key, mixed $value): string
    {
        if ($value instanceof UnitEnum) {
            return method_exists($value, 'label')
                ? (string) $value->label()
                : (string) ($value instanceof \BackedEnum ? $value->value : $value->name);
        }

        if ($value instanceof CarbonInterface) {
            return $value->timezone('Europe/Istanbul')->format('d.m.Y H:i');
        }

        $resolved = $this->relatedLabel($key, $value);

        if ($resolved !== null) {
            return $resolved;
        }

        if (is_bool($value) || in_array($key, ['is_published', 'registration_open'], true)) {
            return in_array($value, [true, 1, '1'], true) ? 'Evet' : 'Hayır';
        }

        if ($value === null) {
            return '—';
        }

        if (is_array($value)) {
            $encoded = json_encode($value, JSON_UNESCAPED_UNICODE);

            return Str::limit(is_string($encoded) ? $encoded : 'liste', 80);
        }

        $text = trim(strip_tags((string) $value));

        if ($text === '') {
            return 'güncellendi';
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}(?:[ T]\d{2}:\d{2})/', $text) === 1) {
            return Carbon::parse($text)->timezone('Europe/Istanbul')->format('d.m.Y H:i');
        }

        return Str::limit($this->humanizeStoredValue($key, $text), 80);
    }

    private function humanizeStoredValue(string $key, string $text): string
    {
        if ($key === 'type') {
            return match ($text) {
                'article', 'articles' => 'Yazı',
                'announcement', 'announcements' => 'Duyuru',
                'photo' => 'Fotoğraf',
                'video' => 'Video',
                'audio' => 'Ses',
                'ders' => 'Ders',
                'sohbet' => 'Sohbet',
                'kitap_tahlili' => 'Kitap tahlili',
                default => $text,
            };
        }

        if ($key === 'role') {
            return UserRole::tryFrom($text)?->label() ?? $text;
        }

        if ($key === 'status') {
            return ApplicationStatus::tryFrom($text)?->label() ?? $text;
        }

        return $text;
    }

    private function relatedLabel(string $key, mixed $value): ?string
    {
        if (! is_numeric($value)) {
            return null;
        }

        $id = (int) $value;

        return match ($key) {
            'category_id' => Category::query()->find($id)?->name,
            'author_id' => User::query()->find($id)?->name,
            'event_id' => Event::query()->find($id)?->title,
            'media_album_id' => MediaAlbum::query()->find($id)?->title,
            default => null,
        };
    }
}
