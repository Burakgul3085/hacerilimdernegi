@props(['program'])

<a href="{{ route('programs.show', $program) }}" class="group card card-hover flex flex-col overflow-hidden">
    <x-cover :src="$program->image" :alt="$program->title" ratio="aspect-[16/10]" />

    <div class="flex flex-1 flex-col p-6">
        <p class="tag">{{ $program->type?->label() }}</p>
        <h3 class="mt-2 font-display text-2xl leading-snug text-forest">{{ $program->title }}</h3>

        <div class="mt-4 flex flex-col gap-2">
            <x-meta icon="calendar">
                {{ $program->starts_at?->translatedFormat('d.m.Y, H:i') ?? 'Tarih duyurulacak' }}
            </x-meta>
            @if ($program->instructor)
                <x-meta icon="mic">{{ $program->instructor }}</x-meta>
            @endif
            @if ($program->location)
                <x-meta icon="pin">{{ $program->location }}</x-meta>
            @endif
        </div>

        <div class="mt-6 flex items-center justify-between border-t border-line pt-5">
            <span class="text-sm font-semibold text-forest">Program detayı</span>
            <span class="arrow-btn"><x-ui.icon name="arrow-right" class="h-4 w-4" /></span>
        </div>
    </div>
</a>
