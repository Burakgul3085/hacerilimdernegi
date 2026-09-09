@props(['items' => []])

@if (filled($items))
    <nav aria-label="Sayfa yolu" {{ $attributes->merge(['class' => 'flex flex-wrap items-center gap-2 text-[13px] text-muted']) }}>
        <a href="{{ route('home') }}" class="transition hover:text-gold">Ana sayfa</a>
        @foreach ($items as $item)
            <x-ui.icon name="chevron-right" class="h-3 w-3 text-line" />
            @if (! empty($item['url']) && ! $loop->last)
                <a href="{{ $item['url'] }}" class="transition hover:text-gold">{{ $item['label'] }}</a>
            @else
                <span class="text-forest" @if($loop->last) aria-current="page" @endif>{{ $item['label'] }}</span>
            @endif
        @endforeach
    </nav>
@endif
