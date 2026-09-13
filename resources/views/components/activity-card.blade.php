@props(['activity'])

<a href="{{ route('activities.show', $activity) }}" class="group card card-hover activity-card flex flex-col overflow-hidden">
    <x-cover :src="$activity->image" :alt="$activity->title" ratio="aspect-[16/10]" />

    <div class="flex flex-1 flex-col px-6 py-5 sm:px-7 sm:py-6">
        <p @class([
            'activity-status',
            'is-ongoing' => $activity->status === \App\Enums\ActivityStatus::Ongoing,
            'is-done' => $activity->status === \App\Enums\ActivityStatus::Completed,
        ])>{{ $activity->status->label() }}</p>

        <h3 class="mt-3 font-display text-[1.65rem] leading-snug text-forest">{{ $activity->title }}</h3>

        @if (filled($activity->excerpt))
            <p class="mt-3 line-clamp-2 text-[15px] leading-relaxed text-muted">{{ $activity->excerpt }}</p>
        @endif

        <div class="mt-auto flex items-center justify-between border-t border-line pt-5">
            <span class="text-[13px] font-medium tracking-wide text-forest/70">İncele</span>
            <span class="arrow-btn"><x-ui.icon name="arrow-right" class="h-4 w-4" /></span>
        </div>
    </div>
</a>
