@props(['announcement'])

<a href="{{ route('announcements.show', $announcement) }}" class="group post-card card card-hover flex flex-col overflow-hidden">
    <div class="post-card-media overflow-hidden">
        <x-cover :src="$announcement->image" :alt="$announcement->title" fit="contain" class="media-zoom" />
    </div>

    <div class="flex flex-1 flex-col p-6">
        <p class="tag">Duyuru</p>
        <h3 class="mt-2 font-display text-2xl leading-snug text-forest transition duration-300 group-hover:text-gold">{{ $announcement->title }}</h3>

        @if ($announcement->excerpt)
            <p class="mt-3 line-clamp-3 text-sm leading-relaxed text-muted">{{ $announcement->excerpt }}</p>
        @endif

        <div class="mt-auto pt-6">
            <div class="flex items-center justify-between border-t border-line pt-5">
                <x-meta icon="calendar">{{ $announcement->published_at?->translatedFormat('d F Y') ?? '' }}</x-meta>
                <span class="arrow-btn"><x-ui.icon name="arrow-right" class="h-4 w-4" /></span>
            </div>
        </div>
    </div>
</a>
