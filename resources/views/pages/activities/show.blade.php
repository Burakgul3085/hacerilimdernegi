@extends('layouts.app')

@section('title', $activity->title)
@section('description', $activity->excerpt ?: \Illuminate\Support\Str::limit(strip_tags($activity->description ?? ''), 160))

@section('content')

<x-page-header
    :eyebrow="$activity->status->label()"
    :title="$activity->title"
    :lead="$activity->excerpt"
    :breadcrumbs="[['label' => 'Faaliyetler', 'url' => route('activities.index')], ['label' => $activity->title]]" />

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

        <aside class="reveal lg:sticky lg:top-32 lg:self-start">
            <div class="card p-6">
                <p class="eyebrow">Katılım</p>
                <p class="mt-3 text-[15px] leading-relaxed text-muted">
                    @if ($sessions->isNotEmpty())
                        Bu hattın yaklaşan oturumları aşağıda. Katılım, ilgili ders veya program sayfasından bildirilir.
                    @else
                        Bu hattın tarihli oturumu duyurulunca burada görünür. Dernek çalışmalarına katılmak için üyelik formunu kullanabilirsiniz.
                    @endif
                </p>

                @if ($sessions->isNotEmpty())
                    <a href="#oturumlar" class="btn btn-solid btn-sm mt-6 w-full">Yaklaşan oturumlar</a>
                @else
                    <a href="{{ route('membership') }}" class="btn btn-solid btn-sm mt-6 w-full">Üyelik / gönüllü</a>
                @endif

                <a href="{{ route('donate') }}" class="btn btn-outline btn-sm mt-3 w-full">Destek olun</a>
            </div>
        </aside>
    </div>

    @if ($sessions->isNotEmpty())
        <div id="oturumlar" class="mt-16 scroll-mt-28 lg:mt-20">
            <div class="mb-8 flex items-center gap-5">
                <h2 class="font-display text-2xl text-gold">Yaklaşan oturumlar</h2>
                <span class="rule flex-1"></span>
            </div>

            @foreach ($sessions as $item)
                <div class="reveal" style="--reveal-delay: {{ $loop->index * 70 }}ms">
                    <x-work-row :item="$item" />
                </div>
            @endforeach
        </div>
    @endif

    @if ($related->isNotEmpty())
        <div class="mt-16 border-t border-line pt-14 lg:mt-20">
            <div class="mb-8 flex items-center gap-5">
                <h2 class="font-display text-2xl text-gold">Diğer faaliyetler</h2>
                <span class="rule flex-1"></span>
            </div>

            <div class="grid gap-6 md:grid-cols-3">
                @foreach ($related as $item)
                    <x-activity-card :activity="$item" />
                @endforeach
            </div>
        </div>
    @endif
</section>

@endsection
