@props(['album'])

<a href="{{ $album->publicUrl() }}" class="group card card-hover album-card flex h-full flex-col overflow-hidden">
    <x-cover
        :src="$album->cover"
        :alt="$album->title"
        fit="frame"
        ratio="aspect-[4/5]"
        class="album-card-media"
    />

    <div class="album-card-body flex flex-1 flex-col px-4 py-3.5">
        <p class="tag">{{ $album->cardLabel() }}</p>

        <h3 class="mt-2 line-clamp-2 min-h-[2.6em] font-display text-[1.15rem] leading-snug text-forest transition duration-300 group-hover:text-gold">
            {{ $album->title }}
        </h3>

        <p class="mt-1.5 line-clamp-2 min-h-[2.6em] text-[13px] leading-relaxed text-muted">
            {{ $album->description }}
        </p>

        <div class="mt-auto flex items-center justify-between border-t border-line pt-2.5">
            <x-meta icon="{{ $album->isCollection() ? 'folder' : 'photo' }}">{{ $album->cardCountLabel() }}</x-meta>
            <span class="arrow-btn"><x-ui.icon name="arrow-right" class="h-4 w-4" /></span>
        </div>
    </div>
</a>
