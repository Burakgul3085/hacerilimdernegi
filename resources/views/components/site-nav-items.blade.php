@props([
    'items' => [],
    'variant' => 'desktop',
])

@if ($variant === 'desktop')
    @foreach ($items as $item)
        @if (($item['children'] ?? []) !== [])
            <div class="relative" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false">
                <button type="button"
                        class="nav-link inline-flex cursor-pointer items-center gap-1.5 bg-transparent p-0"
                        data-active="{{ ($item['active'] ?? false) ? '1' : '0' }}"
                        @click="open = !open"
                        @keydown.escape="open = false"
                        :aria-expanded="open"
                        aria-haspopup="true">
                    {{ $item['label'] ?? '' }}
                    <x-ui.icon name="chevron-down" class="nav-caret h-3 w-3" x-bind:class="open && 'is-open'" />
                </button>
                <div class="nav-dropdown-anchor"
                     x-show="open"
                     x-cloak
                     x-transition:enter="nav-dropdown-enter"
                     x-transition:enter-start="nav-dropdown-enter-start"
                     x-transition:enter-end="nav-dropdown-enter-end"
                     x-transition:leave="nav-dropdown-leave"
                     x-transition:leave-start="nav-dropdown-leave-start"
                     x-transition:leave-end="nav-dropdown-leave-end">
                    <div class="nav-dropdown">
                        @foreach ($item['children'] as $child)
                            <a href="{{ $child['url'] ?? '#' }}"
                               class="nav-dropdown-link"
                               data-active="{{ ($child['active'] ?? false) ? '1' : '0' }}"
                               @if ($child['active'] ?? false) aria-current="page" @endif>
                                <span>{{ $child['label'] ?? '' }}</span>
                                <x-ui.icon name="arrow-up-right" class="nav-dropdown-arrow h-3.5 w-3.5" />
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
                        <x-ui.icon name="chevron-down" class="h-4 w-4 text-gold transition duration-300" x-bind:class="open && 'rotate-180'" />
                    </button>
                    <ul x-show="open"
                        x-cloak
                        x-transition:enter="transition duration-200 ease-out"
                        x-transition:enter-start="opacity-0 -translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        class="mt-1 space-y-0.5 border-l border-gold/30 pb-2 pl-3">
                        @foreach ($item['children'] as $child)
                            <li>
                                <a href="{{ $child['url'] ?? '#' }}"
                                   class="block rounded-lg px-3 py-2 text-[15px] tracking-wide text-cream/75 transition duration-300 hover:bg-white/5 hover:text-cream">
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
