<?php

namespace App\Http\Controllers;

use App\Models\MediaAlbum;
use App\Models\MediaItem;
use App\Support\UploadRules;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class MediaController extends Controller
{
    public function index(): View
    {
        return view('pages.media.index', [
            'albums' => MediaAlbum::query()
                ->published()
                ->roots()
                ->withCount(['items', 'children' => fn ($query) => $query->published()])
                ->latest()
                ->paginate(9),
        ]);
    }

    public function show(Request $request, MediaAlbum $album): View
    {
        return $this->present($request, $album);
    }

    public function showChild(Request $request, MediaAlbum $album, string $child): View
    {
        abort_unless($album->is_published && $album->parent_id === null, 404);

        $nested = MediaAlbum::query()
            ->published()
            ->where('parent_id', $album->id)
            ->where('slug', $child)
            ->firstOrFail();

        return $this->present($request, $nested);
    }

    private function present(Request $request, MediaAlbum $album): View
    {
        abort_unless($album->isVisibleOnSite(), 404);

        $album->load('parent');

        $query = mb_substr(trim($request->string('q')->toString()), 0, 80);
        $type = $request->string('tur')->toString();

        if (! in_array($type, ['photo', 'video', 'audio', 'link'], true)) {
            $type = '';
        }

        $children = $album->parent_id === null
            ? $album->children()
                ->published()
                ->withCount('items')
                ->when($query !== '', fn ($builder) => $builder->where('title', 'like', '%'.addcslashes($query, '%_').'%'))
                ->orderBy('title')
                ->get()
            : collect();

        $isCollection = $album->parent_id === null && ($children->isNotEmpty() || $query !== '' || $album->children()->published()->exists());

        $album->load('items');
        $groups = $this->mediaGroups($album->items);

        return view('pages.media.show', [
            'album' => $album,
            'isCollection' => $isCollection,
            'children' => $children,
            'query' => $query,
            'type' => $type,
            'groups' => $groups,
        ]);
    }

    /**
     * @param  Collection<int, MediaItem>  $items
     * @return array{photos: Collection, videos: Collection, audios: Collection, links: Collection}
     */
    private function mediaGroups(Collection $items): array
    {
        return [
            'photos' => $items->filter(fn ($item): bool => filled($item->path) && UploadRules::isImagePath($item->path))->values(),
            'videos' => $items->filter(fn ($item): bool => filled($item->path) && UploadRules::isVideoPath($item->path))->values(),
            'audios' => $items->filter(fn ($item): bool => filled($item->path) && UploadRules::isAudioPath($item->path))->values(),
            'links' => $items->filter(fn ($item): bool => filled($item->external_url))->values(),
        ];
    }
}
