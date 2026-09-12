@props([
    'context' => '',
    'tone' => 'light',
])

@php
    $message = trim((string) session('status'));
    $matches = $message !== '' && (string) session('status_context') === $context;
    $parts = $matches ? (preg_split('/(?<=[.!?])\s+/u', $message, 2) ?: [$message]) : [];
    $title = rtrim((string) ($parts[0] ?? $message), '.!?…');
    $detail = isset($parts[1]) ? trim((string) $parts[1]) : '';
@endphp

@if ($matches)
    <div
        {{ $attributes->class(['flash-status-wrap', 'flash-status-wrap-dark' => $tone === 'dark']) }}
        id="form-status-{{ $context }}"
        x-data="{ open: true }"
        x-show="open"
        x-transition.opacity.duration.280ms
        role="status"
        aria-live="polite"
    >
        <div class="flash-status">
            <span class="flash-status-mark" aria-hidden="true">
                <x-ui.icon name="check" class="h-5 w-5" />
            </span>

            <div class="min-w-0">
                <p class="eyebrow">İletildi</p>
                <p class="flash-status-title">{{ $title }}</p>
                @if ($detail !== '')
                    <p class="flash-status-text">{{ $detail }}</p>
                @endif
            </div>

            <button type="button" class="flash-status-close" x-on:click="open = false">
                <span class="sr-only">Kapat</span>
                <x-ui.icon name="close" class="h-4 w-4" />
            </button>
        </div>
    </div>
@endif
