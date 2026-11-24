<?php

namespace App\Models;

use Database\Factories\RegistrationSpreadsheetShareLinkFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * E-tablonun Excel'ini giriş yapmadan indirmeye yarayan süreli paylaşım bağlantısı.
 * Bağlantıdaki anahtarın yalnızca özeti saklanır.
 */
class RegistrationSpreadsheetShareLink extends Model
{
    /** @use HasFactory<RegistrationSpreadsheetShareLinkFactory> */
    use HasFactory;

    /**
     * @var list<int>
     */
    public const DURATION_HOURS = [1, 24, 72];

    protected $fillable = [
        'registration_spreadsheet_id', 'user_id', 'token_hash', 'expires_at', 'single_use',
        'download_count', 'last_downloaded_at', 'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'single_use' => 'boolean',
            'download_count' => 'integer',
            'last_downloaded_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    /**
     * Yeni bağlantı üretir; düz anahtar yalnızca bu dönüşte bilinir.
     *
     * @return array{link: self, token: string}
     */
    public static function issue(RegistrationSpreadsheet $spreadsheet, ?int $userId, int $hours, bool $singleUse): array
    {
        $token = Str::random(48);

        $link = static::query()->create([
            'registration_spreadsheet_id' => $spreadsheet->getKey(),
            'user_id' => $userId,
            'token_hash' => static::hashToken($token),
            'expires_at' => now()->addHours($hours),
            'single_use' => $singleUse,
        ]);

        return ['link' => $link, 'token' => $token];
    }

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    public static function findByToken(string $token): ?self
    {
        return static::query()->where('token_hash', static::hashToken($token))->first();
    }

    public static function durationLabel(int $hours): string
    {
        return match ($hours) {
            1 => '1 saat',
            72 => '3 gün',
            default => $hours.' saat',
        };
    }

    public function isActive(): bool
    {
        return $this->revoked_at === null
            && $this->expires_at->isFuture()
            && ! ($this->single_use && $this->download_count > 0);
    }

    public function statusLabel(): string
    {
        return match (true) {
            $this->revoked_at !== null => 'Kapatıldı',
            $this->single_use && $this->download_count > 0 => 'Kullanıldı',
            $this->expires_at->isPast() => 'Süresi doldu',
            default => 'Açık',
        };
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->where(fn (Builder $builder) => $builder->where('single_use', false)->orWhere('download_count', 0));
    }

    public function spreadsheet(): BelongsTo
    {
        return $this->belongsTo(RegistrationSpreadsheet::class, 'registration_spreadsheet_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
