<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PostController extends Controller
{
    public function index(Request $request): View
    {
        $posts = Post::query()
            ->published()
            ->when($request->filled('tur'), fn ($query) => $query->where('type', $request->string('tur')))
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

        return view('pages.posts.show', compact('post'));
    }
}
