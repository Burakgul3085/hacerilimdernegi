<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PostController extends Controller
{
    public function index(Request $request): View
    {
        $posts = Post::query()
            ->with('category')
            ->published()
            ->when($request->filled('tur'), fn (Builder $query) => $query->where('type', $request->string('tur')))
            ->latest('published_at')
            ->latest()
            ->paginate(9)
            ->withQueryString();

        return view('pages.posts.index', [
            'posts' => $posts,
            'currentType' => $request->string('tur')->toString(),
        ]);
    }

    public function show(Post $post): View
    {
        abort_unless($post->isVisibleOnSite(), 404);

        $post->load(['author', 'category']);

        $related = Post::query()
            ->with('category')
            ->published()
            ->whereKeyNot($post->getKey())
            ->orderByRaw('CASE WHEN type = ? THEN 0 ELSE 1 END', [$post->type])
            ->latest('published_at')
            ->latest()
            ->limit(3)
            ->get();

        $shareUrl = route('posts.show', $post, absolute: true);
        $shareText = $post->title.' — '.$shareUrl;

        return view('pages.posts.show', [
            'post' => $post,
            'related' => $related,
            'previous' => $this->neighbouringPost($post, previous: true),
            'next' => $this->neighbouringPost($post, previous: false),
            'shareUrl' => $shareUrl,
            'whatsappShareUrl' => 'https://wa.me/?text='.rawurlencode($shareText),
        ]);
    }

    private function neighbouringPost(Post $post, bool $previous): ?Post
    {
        $stamp = $post->published_at ?? $post->created_at;

        return Post::query()
            ->published()
            ->whereKeyNot($post->getKey())
            ->where(function (Builder $query) use ($stamp, $post, $previous): void {
                $query->where('published_at', $previous ? '<' : '>', $stamp)
                    ->orWhere(function (Builder $inner) use ($stamp, $post, $previous): void {
                        $inner->where('published_at', $stamp)
                            ->where('id', $previous ? '<' : '>', $post->id);
                    });
            })
            ->when(
                $previous,
                fn (Builder $query) => $query->latest('published_at')->latest('id'),
                fn (Builder $query) => $query->oldest('published_at')->oldest('id'),
            )
            ->first();
    }
}
