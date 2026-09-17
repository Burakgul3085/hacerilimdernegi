@props(['activity'])

<a href="{{ route('activities.show', $activity) }}" class="group card card-hover activity-card flex h-full flex-col overflow-hidden">
    <x-cover
        :src="$activity->image"
        :alt="$activity->title"
        fit="frame"
        ratio="aspect-[4/5]"
        class="activity-card-media"
    />

    <div class="activity-card-body flex flex-1 flex-col px-4 py-3.5">
        <p @class([
            'activity-status',
            'is-ongoing' => $activity->status === \App\Enums\ActivityStatus::Ongoing,
            'is-done' => $activity->status === \App\Enums\ActivityStatus::Completed,
        ])>{{ $activity->status->label() }}</p>

        <h3 class="mt-2 line-clamp-2 min-h-[2.6em] font-display text-[1.15rem] leading-snug text-forest">{{ $activity->title }}</h3>

        <p class="mt-1.5 line-clamp-2 min-h-[2.6em] text-[13px] leading-relaxed text-muted">
            {{ $activity->excerpt }}
        </p>

        <div class="mt-auto border-t border-line pt-2.5">
            @if (filled($activity->sessionHeadline()))
                <p class="activity-session-line line-clamp-1">{{ $activity->sessionHeadline() }}</p>
            @else
                <p class="activity-session-line invisible select-none" aria-hidden="true">—</p>
            @endif

            <div class="mt-2 flex items-center justify-between">
                <span class="text-[12px] font-medium tracking-wide text-forest/70">İncele</span>
                <span class="arrow-btn"><x-ui.icon name="arrow-right" class="h-4 w-4" /></span>
            </div>
        </div>
    </div>
</a>
