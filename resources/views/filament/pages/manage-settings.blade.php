<x-filament-panels::page>
    <form wire:submit="save" class="space-y-8">
        <x-filament::section>
            <x-slot name="heading">Kurum</x-slot>
            <div class="grid gap-4 md:grid-cols-2">
                <x-filament::input.wrapper>
                    <x-filament::input type="text" wire:model="site_name" placeholder="Dernek adı" />
                </x-filament::input.wrapper>
                <x-filament::input.wrapper>
                    <x-filament::input type="text" wire:model="tagline" placeholder="Kısa slogan" />
                </x-filament::input.wrapper>
            </div>
            <div class="mt-4">
                <textarea wire:model="about_excerpt" rows="3" class="w-full rounded-lg border-gray-300 text-sm"></textarea>
            </div>
            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <div>
                    <p class="mb-1 text-sm font-medium">Logo</p>
                    <input type="file" wire:model="logo" accept="image/*">
                </div>
                <div>
                    <p class="mb-1 text-sm font-medium">Favicon</p>
                    <input type="file" wire:model="favicon" accept="image/*">
                </div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">İletişim ve sosyal</x-slot>
            <div class="grid gap-4 md:grid-cols-2">
                <x-filament::input.wrapper>
                    <x-filament::input type="text" wire:model="address" placeholder="Adres" />
                </x-filament::input.wrapper>
                <x-filament::input.wrapper>
                    <x-filament::input type="text" wire:model="phone" placeholder="Telefon" />
                </x-filament::input.wrapper>
                <x-filament::input.wrapper>
                    <x-filament::input type="email" wire:model="email" placeholder="E-posta" />
                </x-filament::input.wrapper>
                <x-filament::input.wrapper>
                    <x-filament::input type="text" wire:model="domain" placeholder="Domain" />
                </x-filament::input.wrapper>
                <x-filament::input.wrapper>
                    <x-filament::input type="url" wire:model="telegram" placeholder="Telegram URL" />
                </x-filament::input.wrapper>
                <x-filament::input.wrapper>
                    <x-filament::input type="url" wire:model="whatsapp" placeholder="WhatsApp kanal URL" />
                </x-filament::input.wrapper>
                <x-filament::input.wrapper>
                    <x-filament::input type="url" wire:model="twitter" placeholder="X / Twitter URL" />
                </x-filament::input.wrapper>
                <x-filament::input.wrapper>
                    <x-filament::input type="url" wire:model="instagram" placeholder="Instagram URL" />
                </x-filament::input.wrapper>
                <x-filament::input.wrapper>
                    <x-filament::input type="url" wire:model="youtube" placeholder="YouTube URL" />
                </x-filament::input.wrapper>
                <x-filament::input.wrapper>
                    <x-filament::input type="url" wire:model="facebook" placeholder="Facebook URL" />
                </x-filament::input.wrapper>
            </div>
            <div class="mt-4">
                <textarea wire:model="map_embed" rows="3" class="w-full rounded-lg border-gray-300 text-sm" placeholder="Google Maps iframe"></textarea>
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Canlı yayın ve bağış</x-slot>
            <label class="mb-4 flex items-center gap-2 text-sm">
                <input type="checkbox" wire:model="live_is_active"> Canlı yayın aktif
            </label>
            <div class="grid gap-4 md:grid-cols-2">
                <x-filament::input.wrapper>
                    <x-filament::input type="url" wire:model="live_youtube_url" placeholder="YouTube yayın URL" />
                </x-filament::input.wrapper>
                <x-filament::input.wrapper>
                    <x-filament::input type="url" wire:model="live_instagram_url" placeholder="Instagram yayın URL" />
                </x-filament::input.wrapper>
                <x-filament::input.wrapper>
                    <x-filament::input type="text" wire:model="iban" placeholder="IBAN" />
                </x-filament::input.wrapper>
                <x-filament::input.wrapper>
                    <x-filament::input type="text" wire:model="bank_name" placeholder="Banka" />
                </x-filament::input.wrapper>
                <x-filament::input.wrapper>
                    <x-filament::input type="text" wire:model="bank_account_name" placeholder="Hesap adı" />
                </x-filament::input.wrapper>
            </div>
            <label class="mt-4 flex items-center gap-2 text-sm">
                <input type="checkbox" wire:model="bank_details_are_demo">
                Banka bilgileri demo; ziyaretçiye ödeme yapmama uyarısı göster
            </label>
            <textarea wire:model="donation_note" rows="3" class="mt-4 w-full rounded-lg border-gray-300 text-sm"></textarea>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Renkler ve yasal metinler</x-slot>
            <div class="grid gap-4 md:grid-cols-2">
                <x-filament::input.wrapper>
                    <x-filament::input type="text" wire:model="color_primary" />
                </x-filament::input.wrapper>
                <x-filament::input.wrapper>
                    <x-filament::input type="text" wire:model="color_gold" />
                </x-filament::input.wrapper>
            </div>
            <textarea wire:model="kvkk_text" rows="6" class="mt-4 w-full rounded-lg border-gray-300 text-sm" placeholder="KVKK aydınlatma"></textarea>
            <textarea wire:model="privacy_text" rows="6" class="mt-4 w-full rounded-lg border-gray-300 text-sm" placeholder="Gizlilik"></textarea>
            <textarea wire:model="cookie_text" rows="4" class="mt-4 w-full rounded-lg border-gray-300 text-sm" placeholder="Çerez"></textarea>
        </x-filament::section>

        <x-filament::button type="submit">Kaydet</x-filament::button>
    </form>
</x-filament-panels::page>
