<?php

namespace App\Support;

use App\Models\Event;
use App\Models\MediaAlbum;
use App\Models\Page;
use App\Models\Post;
use App\Models\Program;
use Carbon\CarbonInterface;

class PublicSitemap
{
    /**
     * Yayındaki kamuya açık sayfaların sitemap girdileri.
     *
     * @return list<array{loc: string, lastmod: string, changefreq: string, priority: string}>
     */
    public function entries(): array
    {
        $now = now();
        $entries = [
            $this->entry(route('home'), $now, 'daily', '1.0'),
            $this->entry(route('about'), $now, 'monthly', '0.8'),
            $this->entry(route('programs.index'), $now, 'weekly', '0.8'),
            $this->entry(route('events.index'), $now, 'weekly', '0.8'),
            $this->entry(route('posts.index'), $now, 'weekly', '0.8'),
            $this->entry(route('media.index'), $now, 'weekly', '0.7'),
            $this->entry(route('social'), $now, 'weekly', '0.6'),
            $this->entry(route('membership'), $now, 'monthly', '0.7'),
            $this->entry(route('donate'), $now, 'monthly', '0.7'),
            $this->entry(route('contact'), $now, 'monthly', '0.7'),
        ];

        foreach (Program::query()->published()->orderBy('id')->get(['slug', 'updated_at']) as $program) {
            $entries[] = $this->entry(route('programs.show', $program), $program->updated_at, 'weekly', '0.7');
        }

        foreach (Event::query()->published()->orderBy('id')->get(['slug', 'updated_at']) as $event) {
            $entries[] = $this->entry(route('events.show', $event), $event->updated_at, 'weekly', '0.7');
        }

        foreach (Post::query()->published()->orderBy('id')->get(['slug', 'updated_at', 'published_at']) as $post) {
            $lastmod = $post->updated_at ?? $post->published_at ?? $now;
            $entries[] = $this->entry(route('posts.show', $post), $lastmod, 'weekly', '0.6');
        }

        foreach (MediaAlbum::query()->published()->orderBy('id')->get(['slug', 'updated_at']) as $album) {
            $entries[] = $this->entry(route('media.show', $album), $album->updated_at, 'weekly', '0.5');
        }

        foreach (Page::query()->published()->orderBy('id')->get(['slug', 'updated_at']) as $page) {
            if ($page->slug === 'hakkimizda') {
                continue;
            }

            $entries[] = $this->entry(route('pages.show', $page->slug), $page->updated_at, 'monthly', '0.6');
        }

        return $entries;
    }

    /**
     * @return array{loc: string, lastmod: string, changefreq: string, priority: string}
     */
    private function entry(string $loc, ?CarbonInterface $lastmod, string $changefreq, string $priority): array
    {
        return [
            'loc' => $loc,
            'lastmod' => ($lastmod ?? now())->toAtomString(),
            'changefreq' => $changefreq,
            'priority' => $priority,
        ];
    }
}
