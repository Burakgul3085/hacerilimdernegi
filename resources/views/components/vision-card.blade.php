@props([
    'title',
    'index',
    'icon',
    'html' => null,
    'delay' => '0ms',
    'direction' => 'left',
    'eyebrow' => 'Kurum',
])

@php
    $revealClass = $direction === 'right' ? 'reveal-right' : 'reveal-left';
@endphp

<article
    {{ $attributes->merge(['class' => 'vision-card cinematic-panel reveal '.$revealClass.($html ? '' : ' vision-card-empty')]) }}
    style="--reveal-delay: {{ $delay }}"
>
    <p class="vision-card-index" aria-hidden="true">{{ $index }}</p>

    <span class="vision-card-icon">
        <x-ui.icon :name="$icon" class="h-6 w-6" />
    </span>

    <p class="eyebrow mt-6">{{ $eyebrow }}</p>
    <h2 class="mt-3 font-display text-4xl leading-tight text-forest sm:text-5xl">{{ $title }}</h2>

    @if ($html)
        <div class="prose-hacer vision-card-body mt-6 max-w-full break-words">{!! $html !!}</div>
    @else
                    <p class="mt-6 text-sm leading-relaxed text-muted">Yazı henüz eklenmedi.</p>
    @endif
</article>
