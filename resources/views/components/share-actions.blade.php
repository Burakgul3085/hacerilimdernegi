@props([
    'shareUrl',
    'whatsappShareUrl',
    'withComment' => false,
])

<div {{ $attributes }} x-data="{ copied: false }">
    <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-gold">Paylaş</p>
    <div class="post-share mt-3">
        <a href="{{ $whatsappShareUrl }}"
           target="_blank"
           rel="noopener noreferrer"
           class="btn btn-outline btn-sm">
            <x-ui.icon name="whatsapp" class="h-4 w-4" />
            WhatsApp
        </a>
        <button type="button"
                class="btn btn-outline btn-sm"
                data-url="{{ $shareUrl }}"
                x-on:click="
                    const value = $el.dataset.url ?? '';
                    if (! value || ! navigator.clipboard?.writeText) {
                        return;
                    }
                    navigator.clipboard.writeText(value).then(() => {
                        copied = true;
                        window.setTimeout(() => copied = false, 1800);
                    }).catch(() => {});
                ">
            <span class="inline-flex items-center gap-1.5" x-show="! copied">
                <x-ui.icon name="clipboard" class="h-4 w-4" />
                Bağlantıyı kopyala
            </span>
            <span class="inline-flex items-center gap-1.5" x-cloak x-show="copied">
                <x-ui.icon name="check" class="h-4 w-4" />
                Kopyalandı
            </span>
        </button>
    </div>
    @if ($withComment)
        <a href="#yorumlar" class="btn btn-solid btn-sm mt-6 w-full">Yorum yaz</a>
    @endif
</div>
