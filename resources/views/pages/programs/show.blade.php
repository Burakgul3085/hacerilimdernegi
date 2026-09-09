@extends('layouts.app')

@section('title', $program->title)
@section('description', \Illuminate\Support\Str::limit(strip_tags($program->description ?? ''), 160))

@section('content')

<x-page-header
    :eyebrow="$program->type?->label()"
    :title="$program->title"
    :breadcrumbs="[['label' => 'Programlar', 'url' => route('programs.index')], ['label' => $program->title]]" />

<section class="shell py-14 lg:py-20">
    <div class="grid gap-12 lg:grid-cols-[minmax(0,1fr)_22rem] lg:gap-16">
        <div class="reveal">
            <x-cover :src="$program->image" :alt="$program->title" ratio="aspect-[16/9]" rounded="rounded-2xl" />
            <div class="prose-hacer mt-10">{!! $program->description !!}</div>
        </div>

        <aside class="reveal lg:sticky lg:top-32 lg:self-start">
            <div class="card p-6">
                <p class="eyebrow">Program bilgileri</p>
                <ul class="mt-4 space-y-3.5">
                    <li><x-meta icon="calendar">{{ $program->starts_at?->translatedFormat('d F Y, H:i') ?? 'Tarih duyurulacak' }}</x-meta></li>
                    @if ($program->ends_at)
                        <li><x-meta icon="clock">Bitiş: {{ $program->ends_at->translatedFormat('d F Y, H:i') }}</x-meta></li>
                    @endif
                    @if ($program->instructor)
                        <li><x-meta icon="mic">{{ $program->instructor }}</x-meta></li>
                    @endif
                    @if ($program->location)
                        <li><x-meta icon="pin">{{ $program->location }}</x-meta></li>
                    @endif
                </ul>
                <a href="{{ route('contact') }}" class="btn btn-solid btn-sm mt-6 w-full">Katılım için bize yazın</a>
            </div>

            @if ($related->isNotEmpty())
                <div class="card mt-4 p-6">
                    <p class="eyebrow">Benzer programlar</p>
                    <ul class="mt-4 divide-y divide-line">
                        @foreach ($related as $item)
                            <li>
                                <a href="{{ route('programs.show', $item) }}" class="group flex items-center justify-between gap-3 py-3">
                                    <span>
                                        <span class="block font-display text-lg leading-snug text-forest">{{ $item->title }}</span>
                                        <span class="mt-0.5 block text-[13px] text-muted">{{ $item->starts_at?->translatedFormat('d.m.Y') }}</span>
                                    </span>
                                    <x-ui.icon name="chevron-right" class="h-4 w-4 shrink-0 text-line transition group-hover:text-gold" />
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </aside>
    </div>
</section>

@endsection
