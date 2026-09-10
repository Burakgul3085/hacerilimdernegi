<div
    x-show="legal"
    x-cloak
    x-transition.opacity.duration.200ms
    class="fixed inset-0 z-[80] flex items-end justify-center p-0 sm:items-center sm:p-6"
    role="dialog"
    aria-modal="true"
    :aria-labelledby="legal ? 'legal-modal-title' : null"
    @keydown.escape.window="closeLegal()"
>
    {{-- Arka plan tıklanınca kapanır; body scroll kilidi yok --}}
    <div class="absolute inset-0 bg-forest-deep/50 backdrop-blur-[2px]" @click="closeLegal()" aria-hidden="true"></div>

    <div
        x-show="legal"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="translate-y-6 opacity-0 sm:translate-y-4 sm:scale-95"
        x-transition:enter-end="translate-y-0 opacity-100 sm:scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="translate-y-0 opacity-100 sm:scale-100"
        x-transition:leave-end="translate-y-6 opacity-0 sm:translate-y-4 sm:scale-95"
        class="relative z-10 flex max-h-[min(92vh,40rem)] w-full max-w-2xl flex-col overflow-hidden rounded-t-3xl border border-line bg-paper shadow-lift sm:max-h-[min(85vh,40rem)] sm:rounded-3xl"
        @click.stop
    >
        <div class="flex items-start justify-between gap-4 border-b border-line px-5 py-4 sm:px-7 sm:py-5">
            <div class="min-w-0 pr-2">
                <p class="eyebrow">Yasal</p>
                <h2 id="legal-modal-title" class="mt-1 font-display text-2xl leading-snug text-forest sm:text-3xl" x-text="legalTitle()"></h2>
            </div>
            <button type="button"
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-line text-forest transition hover:bg-cream"
                    @click="closeLegal()"
                    aria-label="Kapat">
                <x-ui.icon name="close" class="h-5 w-5" />
            </button>
        </div>

        <div class="flex-1 overflow-y-auto overscroll-contain px-5 py-5 sm:px-7 sm:py-6">
            <div class="prose-hacer whitespace-pre-line text-[15px] leading-relaxed text-muted" x-text="legalBody()"></div>
        </div>

        <div class="flex flex-wrap items-center gap-2 border-t border-line bg-cream/60 px-5 py-4 sm:px-7">
            <template x-for="item in legalTabs" :key="item.key">
                <button type="button"
                        class="chip"
                        :class="legal === item.key ? 'chip-active' : ''"
                        @click="openLegal(item.key)"
                        x-text="item.short"></button>
            </template>
            <button type="button" class="btn btn-solid btn-sm ml-auto" @click="closeLegal()">Kapat</button>
        </div>
    </div>
</div>
