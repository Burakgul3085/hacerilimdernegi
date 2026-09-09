@props([
    'eyebrow' => null,
    'title' => '',
    'lead' => null,
    'breadcrumbs' => [],
])

<section class="border-b border-line bg-paper">
    <div class="shell py-10 sm:py-14">
        <x-breadcrumbs :items="$breadcrumbs" />

        <div class="mt-6 flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
            <div class="max-w-2xl">
                @if ($eyebrow)
                    <p class="eyebrow">{{ $eyebrow }}</p>
                @endif
                <h1 class="display-2 mt-3 text-balance">{{ $title }}</h1>
                @if ($lead)
                    <p class="lead mt-4">{{ $lead }}</p>
                @endif
            </div>

            @if (! $slot->isEmpty())
                <div class="shrink-0">{{ $slot }}</div>
            @endif
        </div>
    </div>
</section>
