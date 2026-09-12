@props(['event'])

<div class="group relative flex gap-5 sm:gap-8">
    <div class="flex w-14 shrink-0 flex-col items-center sm:w-20">
        <div class="flex h-14 w-14 flex-col items-center justify-center rounded-full border border-line bg-paper text-forest transition duration-300 group-hover:border-gold group-hover:bg-gold group-hover:text-white sm:h-16 sm:w-16">
            <span class="font-display text-2xl leading-none">{{ $event->starts_at?->format('d') ?? '—' }}</span>
            <span class="mt-0.5 text-[10px] uppercase tracking-[0.18em]">
                {{ $event->starts_at ? mb_strtoupper(mb_substr($event->starts_at->translatedFormat('F'), 0, 3), 'UTF-8') : '' }}
            </span>
        </div>
        <div class="mt-2 w-px flex-1 bg-line"></div>
    </div>

    <a href="{{ route('events.show', $event) }}" class="card card-hover mb-6 flex flex-1 items-center gap-5 p-5 sm:p-6">
        <div class="min-w-0 flex-1">
            <h3 class="font-display text-2xl leading-snug text-forest">{{ $event->title }}</h3>
            <div class="mt-3 flex flex-wrap gap-x-6 gap-y-2">
                <x-meta icon="clock">{{ $event->starts_at?->translatedFormat('H:i') ?? 'Saat duyurulacak' }}</x-meta>
                @if ($event->location)
                    <x-meta icon="pin">{{ $event->location }}</x-meta>
                @endif
                @if ($event->registration_open)
                    <x-meta icon="check">Kayıt açık</x-meta>
                @endif
            </div>
        </div>
        <span class="arrow-btn hidden sm:inline-flex"><x-ui.icon name="arrow-right" class="h-4 w-4" /></span>
    </a>
</div>
