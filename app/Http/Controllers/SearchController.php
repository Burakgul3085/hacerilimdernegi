<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\MediaAlbum;
use App\Models\Page;
use App\Models\Post;
use App\Models\Program;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function __invoke(Request $request): View
    {
        $term = trim((string) $request->string('q'));

        if (mb_strlen($term) > 80) {
            $term = mb_substr($term, 0, 80);
        }

        return view('pages.search', [
            'term' => $term,
            'results' => $term === '' ? collect() : $this->search($term),
        ]);
    }

    /**
     * Yayında olan program, etkinlik, yazı, albüm ve sayfalarda başlık/özet araması yapar.
     *
     * @return Collection<int, array{label: string, title: string, excerpt: ?string, url: string, icon: string}>
     */
    private function search(string $term): Collection
    {
        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $term).'%';

        $programs = Program::query()
            ->published()
            ->where(fn ($query) => $query->where('title', 'like', $like)->orWhere('instructor', 'like', $like)->orWhere('description', 'like', $like))
            ->orderBy('starts_at')
            ->limit(10)
            ->get()
            ->map(fn (Program $program) => [
                'label' => $program->type?->label() ?? 'Program',
                'title' => $program->title,
                'excerpt' => $program->starts_at?->translatedFormat('d F Y, H:i'),
                'url' => route('programs.show', $program),
                'icon' => 'cap',
            ]);

        $events = Event::query()
            ->published()
            ->where(fn ($query) => $query->where('title', 'like', $like)->orWhere('description', 'like', $like)->orWhere('location', 'like', $like))
            ->orderBy('starts_at')
            ->limit(10)
            ->get()
            ->map(fn (Event $event) => [
                'label' => 'Etkinlik',
                'title' => $event->title,
                'excerpt' => $event->starts_at?->translatedFormat('d F Y, H:i'),
                'url' => route('events.show', $event),
                'icon' => 'calendar',
            ]);

        $posts = Post::query()
            ->published()
            ->where(fn ($query) => $query->where('title', 'like', $like)->orWhere('excerpt', 'like', $like)->orWhere('body', 'like', $like))
            ->latest('published_at')
            ->limit(10)
            ->get()
            ->map(fn (Post $post) => [
                'label' => $post->type === 'announcement' ? 'Duyuru' : 'Yazı',
                'title' => $post->title,
                'excerpt' => $post->excerpt,
                'url' => route('posts.show', $post),
                'icon' => 'document',
            ]);

        $albums = MediaAlbum::query()
            ->published()
            ->where(fn ($query) => $query->where('title', 'like', $like)->orWhere('description', 'like', $like))
            ->limit(10)
            ->get()
            ->map(fn (MediaAlbum $album) => [
                'label' => 'Medya',
                'title' => $album->title,
                'excerpt' => $album->description,
                'url' => route('media.show', $album),
                'icon' => 'photo',
            ]);

        $pages = Page::query()
            ->published()
            ->where(fn ($query) => $query->where('title', 'like', $like)->orWhere('excerpt', 'like', $like)->orWhere('body', 'like', $like))
            ->limit(10)
            ->get()
            ->map(fn (Page $page) => [
                'label' => 'Sayfa',
                'title' => $page->title,
                'excerpt' => $page->excerpt,
                'url' => $page->slug === 'hakkimizda' ? route('about') : route('pages.show', $page->slug),
                'icon' => 'building',
            ]);

        return collect()
            ->concat($programs)
            ->concat($events)
            ->concat($posts)
            ->concat($albums)
            ->concat($pages);
    }
}
