@props(['icon' => 'calendar'])

<span {{ $attributes->merge(['class' => 'inline-flex items-start gap-2 text-sm text-muted']) }}>
    <x-ui.icon :name="$icon" class="mt-0.5 h-4 w-4 shrink-0 text-gold" />
    <span>{{ $slot }}</span>
</span>
