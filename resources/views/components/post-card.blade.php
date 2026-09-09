@props(['post'])

<a href="{{ route('posts.show', $post) }}" class="group card card-hover flex flex-col overflow-hidden">
    <x-cover :src="$post->image" :alt="$post->title" ratio="aspect-[16/9]" />

    <div class="flex flex-1 flex-col p-6">
        <p class="tag">{{ $post->type === 'announcement' ? 'Duyuru' : 'Yazı' }}</p>
        <h3 class="mt-2 font-display text-2xl leading-snug text-forest">{{ $post->title }}</h3>

        @if ($post->excerpt)
            <p class="mt-3 line-clamp-3 text-sm leading-relaxed text-muted">{{ $post->excerpt }}</p>
        @endif

        <div class="mt-auto pt-6">
            <div class="flex items-center justify-between border-t border-line pt-5">
                <x-meta icon="calendar">{{ $post->published_at?->translatedFormat('d F Y') ?? '' }}</x-meta>
                <span class="arrow-btn"><x-ui.icon name="arrow-right" class="h-4 w-4" /></span>
            </div>
        </div>
    </div>
</a>
