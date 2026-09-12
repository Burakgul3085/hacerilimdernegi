<?php

namespace App\Models;

use App\Enums\BoardTier;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasContentGallery;
use App\Support\CorporatePages;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Page extends Model
{
    use Auditable;
    use HasContentGallery;

    protected $fillable = [
        'slug', 'title', 'excerpt', 'body', 'image', 'gallery', 'document', 'board_members', 'president_name', 'president_title', 'vision', 'mission', 'seo_title', 'seo_description', 'is_published',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'board_members' => 'array',
            'gallery' => 'array',
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

    public function isMessage(): bool
    {
        return CorporatePages::isMessage((string) $this->slug);
    }

    public function isVision(): bool
    {
        return CorporatePages::isVision((string) $this->slug);
    }

    public function isAbout(): bool
    {
        return CorporatePages::isAbout((string) $this->slug);
    }

    public function visionHtml(): ?string
    {
        return $this->filledHtml($this->vision);
    }

    public function missionHtml(): ?string
    {
        return $this->filledHtml($this->mission);
    }

    public function presidentName(): ?string
    {
        $name = trim((string) $this->president_name);

        return $name !== '' ? $name : null;
    }

    public function presidentTitle(): string
    {
        $title = trim((string) $this->president_title);

        return $title !== '' ? $title : CorporatePages::DEFAULT_PRESIDENT_TITLE;
    }

    public function imageUrl(): ?string
    {
        if (blank($this->image)) {
            return null;
        }

        $image = $this->image;

        if (is_array($image)) {
            $image = $image[0] ?? null;
        }

        if (! is_string($image) || $image === '') {
            return null;
        }

        return Storage::disk('public')->url($image);
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

    private function filledHtml(mixed $html): ?string
    {
        if (! is_string($html)) {
            return null;
        }

        $html = trim($html);

        if ($html === '' || trim(strip_tags($html)) === '') {
            return null;
        }

        return $html;
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
