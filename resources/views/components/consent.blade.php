@props(['name' => 'kvkk_accepted'])

<div class="flex flex-col gap-2">
    <label class="flex items-start gap-3 text-[13px] leading-relaxed text-muted">
        <input type="checkbox" name="{{ $name }}" value="1" required @checked(old($name))
               class="mt-0.5 h-4 w-4 shrink-0 rounded border-line text-forest focus:ring-gold">
        <span>
            <button type="button"
                    class="underline decoration-gold underline-offset-4 hover:text-forest"
                    @click.prevent="openLegal('kvkk')">KVKK aydınlatma metnini</button>
            okudum; verilerimin Almanya (Frankfurt) sunucusunda işlenmesini kabul ediyorum.
        </span>
    </label>

    @error($name)
        <p class="text-[13px] text-red-600">{{ $message }}</p>
    @enderror
</div>
