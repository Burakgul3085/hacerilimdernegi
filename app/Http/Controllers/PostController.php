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
        abort_unless($post->is_published, 404);

        $post->load(['author', 'category']);

        $related = Post::query()
            ->published()
            ->whereKeyNot($post->getKey())
            ->latest('published_at')
            ->latest()
            ->limit(3)
            ->get();

        return view('pages.posts.show', compact('post', 'related'));
    }
}
