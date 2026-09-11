@props([
    'items' => [],
    'variant' => 'desktop',
])

@if ($variant === 'desktop')
    @foreach ($items as $item)
        @if (($item['children'] ?? []) !== [])
            <div class="relative" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false">
                <button type="button"
                        class="nav-link inline-flex cursor-pointer items-center gap-1 bg-transparent p-0"
                        data-active="{{ ($item['active'] ?? false) ? '1' : '0' }}"
                        @click="open = !open"
                        @keydown.escape="open = false"
                        :aria-expanded="open"
                        aria-haspopup="true">
                    {{ $item['label'] ?? '' }}
                    <x-ui.icon name="chevron-down" class="h-3.5 w-3.5 text-gold/80" />
                </button>
                <div x-show="open" x-cloak x-transition.opacity class="absolute left-0 top-full z-50 min-w-[14rem] pt-3">
                    <div class="rounded-2xl border border-line bg-paper py-2 shadow-lift">
                        @foreach ($item['children'] as $child)
                            <a href="{{ $child['url'] ?? '#' }}"
                               class="block px-4 py-2 text-sm font-medium text-forest/80 transition hover:bg-cream hover:text-forest"
                               @if ($child['active'] ?? false) aria-current="page" @endif>
                                {{ $child['label'] ?? '' }}
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        @else
            <a href="{{ $item['url'] ?? '#' }}" class="nav-link" data-active="{{ ($item['active'] ?? false) ? '1' : '0' }}">
                {{ $item['label'] ?? '' }}
            </a>
        @endif
    @endforeach
@else
    @foreach ($items as $item)
        <li>
            @if (($item['children'] ?? []) !== [])
                <div x-data="{ open: {{ ($item['active'] ?? false) ? 'true' : 'false' }} }">
                    <button type="button"
                            class="flex w-full items-center justify-between rounded-xl px-3 py-2.5 text-left font-display text-xl text-cream transition hover:bg-white/5"
                            @click="open = !open"
                            :aria-expanded="open">
                        {{ $item['label'] ?? '' }}
                        <x-ui.icon name="chevron-down" class="h-4 w-4 text-gold transition" x-bind:class="open && 'rotate-180'" />
                    </button>
                    <ul x-show="open" x-cloak class="mt-1 space-y-1 border-l border-white/10 pb-2 pl-4">
                        @foreach ($item['children'] as $child)
                            <li>
                                <a href="{{ $child['url'] ?? '#' }}"
                                   class="block rounded-lg px-3 py-2 text-sm text-cream/75 transition hover:bg-white/5 hover:text-cream">
                                    {{ $child['label'] ?? '' }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @else
                <a href="{{ $item['url'] ?? '#' }}"
                   class="flex items-center justify-between rounded-xl px-3 py-2.5 font-display text-xl text-cream transition hover:bg-white/5">
                    {{ $item['label'] ?? '' }}
                    <x-ui.icon name="chevron-right" class="h-4 w-4 text-gold" />
                </a>
            @endif
        </li>
    @endforeach
@endif
