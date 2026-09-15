<?php

namespace App\Http\Controllers;

use App\Actions\ProcessVisitorPost;
use App\Models\Post;
use App\Support\FormGuard;
use App\Support\FormStatus;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PostController extends Controller
{
    public function index(Request $request): View
    {
        $currentType = Post::normalizeType($request->string('tur')->toString());

        if (! $request->filled('tur') || ! in_array($currentType, ['article', 'poem'], true)) {
            $currentType = '';
        }

        $posts = Post::query()
            ->with('category')
            ->published()
            ->when($currentType !== '', function (Builder $query) use ($currentType): void {
                $query->where('type', $currentType);
            })
            ->latest('published_at')
            ->latest()
            ->paginate(9)
            ->withQueryString();

        return view('pages.posts.index', [
            'posts' => $posts,
            'currentType' => $currentType,
        ]);
    }

    public function store(Request $request, ProcessVisitorPost $process): RedirectResponse
    {
        if (FormGuard::isBot($request)) {
            return FormStatus::redirect('Yazınız bize ulaşmıştır. En kısa zamanda yayımlanacaktır.', 'posts');
        }

        $data = $request->validate([
            'type' => ['required', 'in:article,poem'],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:180'],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:8000'],
            'cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'kvkk_accepted' => ['accepted'],
        ]);

        $body = Post::bodyFromPlainText($data['body']);
        $excerpt = Str::limit(trim(preg_replace('/\s+/u', ' ', $data['body']) ?? $data['body']), 180);

        $coverPath = null;
        if ($request->hasFile('cover')) {
            $coverPath = $request->file('cover')->store('posts', 'public');
        }

        $gallery = [];
        if ($request->hasFile('photo')) {
            $gallery[] = $request->file('photo')->store('posts/gallery', 'public');
        }

        $post = Post::query()->create([
            'type' => $data['type'],
            'title' => $data['title'],
            'slug' => Post::uniqueSlug($data['title']),
            'excerpt' => $excerpt,
            'body' => $body,
            'image' => $coverPath,
            'gallery' => $gallery === [] ? null : $gallery,
            'author_name' => $data['name'],
            'submitter_email' => $data['email'],
            'submitted_from_public' => true,
            'is_published' => false,
            'published_at' => null,
        ]);

        $process->handle($post);

        return FormStatus::redirect('Yazınız bize ulaşmıştır. En kısa zamanda yayımlanacaktır.', 'posts');
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
