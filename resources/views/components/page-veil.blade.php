@props([
    'logoUrl' => null,
])

@php
    $logoUrl ??= \App\Support\SiteSettings::logoUrl();
@endphp

<div {{ $attributes->merge([
    'id' => 'page-veil',
    'class' => 'page-veil',
    'data-page-veil' => '1',
    'aria-hidden' => 'true',
]) }}>
    <div class="page-veil-stage">
        <div class="page-veil-mark">
            <span class="page-veil-ring"></span>
            <img src="{{ $logoUrl }}" alt="" class="page-veil-logo" width="160" height="160">
        </div>
        <p class="page-veil-name">Hâcer</p>
        <p class="page-veil-dots"><span></span><span></span><span></span></p>
    </div>
</div>
