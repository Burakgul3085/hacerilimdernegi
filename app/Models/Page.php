<?php

namespace App\Models;

use App\Enums\BoardTier;
use App\Models\Concerns\Auditable;
use App\Support\CorporatePages;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Page extends Model
{
    use Auditable;

    protected $fillable = [
        'slug', 'title', 'excerpt', 'body', 'image', 'document', 'board_members', 'seo_title', 'seo_description', 'is_published',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'board_members' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Page $page): void {
            if (blank($page->slug) && filled($page->title)) {
                $page->slug = Str::slug($page->title);
            }
        });
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function isBylaws(): bool
    {
        return CorporatePages::isBylaws((string) $this->slug);
    }

    public function isBoard(): bool
    {
        return CorporatePages::isBoard((string) $this->slug);
    }

    /**
     * @return list<array{name: string, title: string, tier: int, photo: ?string, bio: ?string, initials: string}>
     */
    public function boardMembers(): array
    {
        if (! is_array($this->board_members)) {
            return [];
        }

        $members = [];

        foreach ($this->board_members as $member) {
            if (! is_array($member)) {
                continue;
            }

            $name = trim((string) ($member['name'] ?? ''));

            if ($name === '') {
                continue;
            }

            $tier = BoardTier::tryFrom((int) ($member['tier'] ?? 0)) ?? BoardTier::Member;
            $bio = trim((string) ($member['bio'] ?? ''));
            $photo = $member['photo'] ?? null;

            if (is_array($photo)) {
                $photo = $photo[0] ?? null;
            }

            $members[] = [
                'name' => $name,
                'title' => trim((string) ($member['title'] ?? '')) ?: $tier->roleLabel(),
                'tier' => $tier->value,
                'photo' => is_string($photo) && filled($photo) ? $photo : null,
                'bio' => $bio !== '' ? $bio : null,
                'initials' => static::initialsFromName($name),
            ];
        }

        return $members;
    }

    /**
     * @return array<int, list<array{name: string, title: string, tier: int, photo: ?string, bio: ?string, initials: string}>>
     */
    public function boardMembersByTier(): array
    {
        $grouped = [];

        foreach ($this->boardMembers() as $member) {
            $grouped[$member['tier']][] = $member;
        }

        ksort($grouped);

        return $grouped;
    }

    public function documentUrl(): ?string
    {
        if (blank($this->document)) {
            return null;
        }

        return Storage::disk('public')->url($this->document);
    }

    private static function initialsFromName(string $name): string
    {
        return collect(preg_split('/\s+/u', $name) ?: [])
            ->filter()
            ->take(2)
            ->map(fn (string $word): string => mb_strtoupper(mb_substr($word, 0, 1)))
            ->implode('');
    }
}
