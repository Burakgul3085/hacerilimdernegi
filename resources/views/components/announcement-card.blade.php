@props(['announcement'])

<a href="{{ route('announcements.show', $announcement) }}" class="group post-card card card-hover flex h-full flex-col overflow-hidden">
    <x-cover
        :src="$announcement->image"
        :alt="$announcement->title"
        fit="frame"
        ratio="aspect-[4/5]"
        class="post-card-media"
    />

    <div class="post-card-body flex flex-1 flex-col px-4 py-3.5">
        <p class="tag">Duyuru</p>

        <h3 class="mt-2 line-clamp-2 min-h-[2.6em] font-display text-[1.15rem] leading-snug text-forest transition duration-300 group-hover:text-gold">
            {{ $announcement->title }}
        </h3>

        <p class="mt-1.5 line-clamp-2 min-h-[2.6em] text-[13px] leading-relaxed text-muted">
            {{ $announcement->excerpt }}
        </p>

        <div class="mt-auto border-t border-line pt-2.5">
            <div class="flex items-center justify-between">
                <x-meta icon="calendar">{{ $announcement->published_at?->translatedFormat('d F Y') ?? '' }}</x-meta>
                <span class="arrow-btn"><x-ui.icon name="arrow-right" class="h-4 w-4" /></span>
            </div>
        </div>
    </div>
</a>
