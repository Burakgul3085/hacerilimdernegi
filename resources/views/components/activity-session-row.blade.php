@props([
    'session',
    'tone' => 'upcoming',
])

<div {{ $attributes->merge(['class' => 'activity-session-row activity-session-row-'.$tone]) }}>
    <div class="activity-session-day" aria-hidden="true">
        <span class="font-display text-2xl leading-none">{{ $session->starts_at->format('d') }}</span>
        <span class="mt-0.5 text-[10px] uppercase tracking-[0.18em]">{{ mb_strtoupper(mb_substr($session->starts_at->translatedFormat('F'), 0, 3), 'UTF-8') }}</span>
    </div>

    <div class="min-w-0 flex-1">
        <p class="font-display text-xl leading-snug text-forest">{{ $session->longDate() }}</p>
        <div class="mt-2 flex flex-wrap gap-x-5 gap-y-1.5 text-[13px] text-muted">
            <x-meta icon="clock">{{ $session->timeLabel() }}</x-meta>
            @if (filled($session->location))
                <x-meta icon="pin">{{ $session->location }}</x-meta>
            @endif
        </div>
        @if (filled($session->note))
            <p class="mt-2 text-[14px] leading-relaxed text-muted">{{ $session->note }}</p>
        @endif
    </div>
</div>
