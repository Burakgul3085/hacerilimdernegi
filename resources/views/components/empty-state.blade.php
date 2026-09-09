@props([
    'icon' => 'sparkles',
    'title' => 'Henüz kayıt yok',
    'text' => null,
])

<div {{ $attributes->merge(['class' => 'card grain flex flex-col items-center justify-center px-6 py-16 text-center']) }}>
    <span class="flex h-14 w-14 items-center justify-center rounded-full border border-line bg-paper text-gold">
        <x-ui.icon :name="$icon" class="h-6 w-6" />
    </span>
    <p class="mt-5 font-display text-2xl text-forest">{{ $title }}</p>
    @if ($text)
        <p class="mt-2 max-w-md text-sm text-muted">{{ $text }}</p>
    @endif
</div>
