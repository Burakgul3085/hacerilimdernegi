@props([
    'action',
    'context',
])

<div {{ $attributes->class(['reveal mt-16 overflow-hidden rounded-2xl border border-line bg-paper']) }} id="kayit">
    <div class="grid lg:grid-cols-[20rem_minmax(0,1fr)]">
        <div class="grain flex flex-col justify-center bg-cream p-6 sm:p-8 lg:p-10">
            <span class="flex h-12 w-12 items-center justify-center rounded-full border border-line bg-paper text-gold">
                <x-ui.icon name="hand" class="h-6 w-6" />
            </span>
            <p class="mt-5 font-display text-3xl leading-snug text-forest">Katılım başvurusu</p>
            <p class="mt-3 text-sm leading-relaxed text-muted">Formu doldurun, dernek yönetimi sizinle iletişime geçsin.</p>
        </div>

        <form method="POST" action="{{ $action }}" class="relative space-y-5 p-5 sm:p-8 lg:p-10">
            @csrf
            <x-honeypot />
            <x-flash-status :context="$context" />

            <div class="grid gap-5 sm:grid-cols-2">
                <x-field name="name" label="Ad soyad" placeholder="Ad soyad" required autocomplete="name" />
                <x-field name="email" type="email" label="E-posta" placeholder="E-posta" required autocomplete="email" />
                <x-field name="phone" label="Telefon" placeholder="Telefon" autocomplete="tel" class="sm:col-span-2" />
            </div>

            <x-field name="notes" type="textarea" label="Not" rows="4" placeholder="Eklemek istedikleriniz" />

            <x-consent />

            <button type="submit" class="btn btn-solid">
                Başvuruyu gönder
                <x-ui.icon name="arrow-right" class="h-4 w-4" />
            </button>
        </form>
    </div>
</div>
