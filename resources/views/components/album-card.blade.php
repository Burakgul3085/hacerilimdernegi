@props(['album'])

<a href="{{ route('media.show', $album) }}" class="group card card-hover flex flex-col overflow-hidden">
    <x-cover :src="$album->cover" :alt="$album->title" ratio="aspect-[4/3]" />

    <div class="flex flex-1 flex-col p-6">
        <p class="tag">Albüm</p>
        <h3 class="mt-2 font-display text-2xl leading-snug text-forest">{{ $album->title }}</h3>
        @if ($album->description)
            <p class="mt-3 line-clamp-2 text-sm leading-relaxed text-muted">{{ $album->description }}</p>
        @endif
        <div class="mt-6 flex items-center justify-between border-t border-line pt-5">
            <x-meta icon="photo">{{ $album->items_count ?? $album->items()->count() }} içerik</x-meta>
            <span class="arrow-btn"><x-ui.icon name="arrow-right" class="h-4 w-4" /></span>
        </div>
    </div>
</a>
