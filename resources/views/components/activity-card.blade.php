@props(['activity'])

<a href="{{ route('activities.show', $activity) }}" class="group card card-hover activity-card flex flex-col overflow-hidden">
    <x-cover :src="$activity->image" :alt="$activity->title" ratio="aspect-[16/9]" />

    <div class="flex flex-1 flex-col px-5 py-4">
        <p @class([
            'activity-status',
            'is-ongoing' => $activity->status === \App\Enums\ActivityStatus::Ongoing,
            'is-done' => $activity->status === \App\Enums\ActivityStatus::Completed,
        ])>{{ $activity->status->label() }}</p>

        <h3 class="mt-2 font-display text-[1.25rem] leading-snug text-forest">{{ $activity->title }}</h3>

        @if (filled($activity->excerpt))
            <p class="mt-2 line-clamp-2 text-[14px] leading-relaxed text-muted">{{ $activity->excerpt }}</p>
        @endif

        <div class="mt-auto border-t border-line pt-3">
            @if (filled($activity->sessionHeadline()))
                <p class="activity-session-line">{{ $activity->sessionHeadline() }}</p>
            @endif

            <div class="mt-2 flex items-center justify-between">
                <span class="text-[13px] font-medium tracking-wide text-forest/70">İncele</span>
                <span class="arrow-btn"><x-ui.icon name="arrow-right" class="h-4 w-4" /></span>
            </div>
        </div>
    </div>
</a>
