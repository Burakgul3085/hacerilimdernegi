@extends('layouts.app')

@section('title', $activity->title)
@section('description', $activity->excerpt ?: \Illuminate\Support\Str::limit(strip_tags($activity->description ?? ''), 160))

@section('content')

<x-page-header
    :eyebrow="$activity->status->label()"
    :title="$activity->title"
    :lead="$activity->excerpt"
    :breadcrumbs="[['label' => 'Faaliyetler', 'url' => route('activities.index')], ['label' => $activity->title]]">
    @if (filled($activity->cadence))
        <p class="activity-cadence">{{ $activity->cadence }}</p>
    @endif
</x-page-header>

<section class="shell py-14 lg:py-20">
    <div class="grid gap-12 lg:grid-cols-[minmax(0,1fr)_21rem] lg:gap-16">
        <div class="reveal">
            <x-cover :src="$activity->image" :alt="$activity->title" ratio="aspect-[16/9]" rounded="rounded-2xl" />
            @if ($activity->galleryMedia() !== [])
                <x-content-gallery class="mt-4" :items="$activity->galleryMedia()" :alt="$activity->title" />
            @endif
            @if (filled($activity->description))
                <div class="prose-hacer mt-10">{!! $activity->description !!}</div>
            @endif
        </div>

        <aside class="reveal reveal-right space-y-4 lg:sticky lg:top-32 lg:self-start">
            @if ($nextSession)
                <article class="activity-next">
                    <p class="eyebrow">Sonraki oturum</p>
                    <p class="mt-3 font-display text-[1.85rem] leading-tight text-forest">{{ $nextSession->longDate() }}</p>
                    <div class="mt-4 flex flex-col gap-2">
                        <x-meta icon="clock">{{ $nextSession->timeLabel() }}</x-meta>
                        @if (filled($nextSession->location))
                            <x-meta icon="pin">{{ $nextSession->location }}</x-meta>
                        @endif
                    </div>
                    @if (filled($nextSession->note))
                        <p class="mt-4 text-[14px] leading-relaxed text-muted">{{ $nextSession->note }}</p>
                    @endif
                </article>
            @endif

            @foreach ($activity->highlightItems() as $highlight)
                <div class="card activity-highlight p-6">
                    <p class="eyebrow">{{ $highlight['title'] }}</p>
                    <p class="mt-3 text-[15px] leading-relaxed text-muted">{{ $highlight['text'] }}</p>
                </div>
            @endforeach

            <div class="card p-6">
                <p class="eyebrow">Katılım</p>
                <p class="mt-3 text-[15px] leading-relaxed text-muted">
                    @if ($activity->acceptsRegistrations())
                        Bu hatta katılmak için formu doldurun. Yönetim size e-posta ile döner.
                    @elseif ($activity->status === \App\Enums\ActivityStatus::Completed)
                        Bu hat tamamlandı.
                    @else
                        Bu hat için kayıt şu an kapalı.
                    @endif
                </p>
                @if ($activity->acceptsRegistrations())
                    <a href="#kayit" class="btn btn-solid btn-sm mt-6 w-full">Katılmak için tıkla</a>
                @endif
            </div>
        </aside>
    </div>

    @if ($upcomingSessions->count() > 1)
        <div class="mt-16 scroll-mt-28 lg:mt-20">
            <div class="mb-8 flex items-center gap-5">
                <h2 class="font-display text-2xl text-gold">Yaklaşan oturumlar</h2>
                <span class="rule flex-1"></span>
            </div>

            <div class="space-y-4">
                @foreach ($upcomingSessions->skip(1) as $session)
                    <div class="reveal" style="--reveal-delay: {{ $loop->index * 80 }}ms">
                        <x-activity-session-row :session="$session" />
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if ($pastSessions->isNotEmpty())
        <div class="mt-16 lg:mt-20">
            <div class="mb-8 flex items-center gap-5">
                <h2 class="font-display text-2xl text-gold">Gerçekleşen</h2>
                <span class="rule flex-1"></span>
            </div>

            <div class="space-y-4">
                @foreach ($pastSessions as $session)
                    <div class="reveal" style="--reveal-delay: {{ $loop->index * 70 }}ms">
                        <x-activity-session-row :session="$session" tone="past" />
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if ($activity->acceptsRegistrations())
        <x-participation-form :action="route('activities.register', $activity)" context="activity" />
    @endif

    @if ($related->isNotEmpty())
        <div class="mt-16 border-t border-line pt-14 lg:mt-20">
            <div class="mb-8 flex items-center gap-5">
                <h2 class="font-display text-2xl text-gold">Diğer faaliyetler</h2>
                <span class="rule flex-1"></span>
            </div>

            <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($related as $item)
                    <div class="reveal" style="--reveal-delay: {{ $loop->index * 80 }}ms">
                        <x-activity-card :activity="$item" />
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</section>

@endsection
