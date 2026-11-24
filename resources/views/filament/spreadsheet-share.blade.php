<div
    x-data="{
        state: 'idle',
        file: null,
        error: '',
        async prepare() {
            this.state = 'loading';
            this.error = '';
            try {
                const response = await fetch(@js($fileUrl), { credentials: 'same-origin' });
                if (! response.ok) { throw new Error('Dosya hazırlanamadı.'); }
                const blob = await response.blob();
                this.file = new File([blob], @js($filename), { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
                const canShare = typeof navigator.canShare === 'function' && navigator.canShare({ files: [this.file] });
                this.state = canShare ? 'ready' : 'unsupported';
            } catch (exception) {
                this.error = exception.message || 'Dosya hazırlanamadı.';
                this.state = 'idle';
            }
        },
        async share() {
            try {
                await navigator.share({ files: [this.file], title: @js($title), text: @js($shareText) });
                this.state = 'done';
            } catch (exception) {
                if (exception && exception.name === 'AbortError') { return; }
                this.state = 'unsupported';
            }
        },
        downloadFile() {
            const url = URL.createObjectURL(this.file);
            const anchor = document.createElement('a');
            anchor.href = url;
            anchor.download = @js($filename);
            anchor.click();
            setTimeout(() => URL.revokeObjectURL(url), 2000);
        },
    }"
    style="display:flex;flex-direction:column;gap:14px;"
>
    <div style="border:1px solid rgba(31,168,85,0.35);background:rgba(31,168,85,0.06);border-radius:14px;padding:16px 18px;">
        <p style="margin:0 0 2px;font-size:11px;font-weight:700;letter-spacing:0.14em;text-transform:uppercase;color:#1f8a4c;">Önerilen</p>
        <p style="margin:0 0 4px;font-size:15px;font-weight:600;">Excel dosyasının kendisini gönderin</p>
        <p style="margin:0 0 14px;font-size:13px;line-height:1.6;opacity:0.75;">
            Cihazınızın paylaşım menüsü açılır; oradan WhatsApp’ı ve kişiyi seçin. Telefonda ve WhatsApp uygulaması kurulu bilgisayarda çalışır. E-tablonun kaydedilmiş hali gönderilir.
        </p>

        <template x-if="state === 'idle'">
            <div>
                <x-filament::button color="success" icon="heroicon-o-document-arrow-down" x-on:click="prepare()">
                    Dosyayı hazırla
                </x-filament::button>
                <p x-show="error" x-text="error" style="margin:8px 0 0;font-size:13px;color:#dc2626;"></p>
            </div>
        </template>

        <template x-if="state === 'loading'">
            <x-filament::button color="success" disabled>
                Hazırlanıyor…
            </x-filament::button>
        </template>

        <template x-if="state === 'ready'">
            <x-filament::button color="success" icon="heroicon-o-share" x-on:click="share()">
                Paylaş
            </x-filament::button>
        </template>

        <template x-if="state === 'done'">
            <p style="margin:0;font-size:14px;font-weight:600;color:#1f8a4c;">Paylaşım menüsü açıldı. WhatsApp’ta kişiyi seçip gönderebilirsiniz.</p>
        </template>

        <template x-if="state === 'unsupported'">
            <div>
                <p style="margin:0 0 10px;font-size:13px;line-height:1.6;">
                    Bu cihaz dosyayı doğrudan paylaşamıyor (örneğin WhatsApp Web kullanılıyorsa). Aşağıdan bir indirme bağlantısı oluşturabilir ya da dosyayı indirip WhatsApp’a kendiniz ekleyebilirsiniz.
                </p>
                <x-filament::button color="gray" icon="heroicon-o-arrow-down-tray" x-on:click="downloadFile()">
                    Dosyayı indir
                </x-filament::button>
            </div>
        </template>
    </div>

    <div style="border:1px solid rgba(120,113,108,0.25);border-radius:14px;padding:16px 18px;">
        <p style="margin:0 0 4px;font-size:15px;font-weight:600;">veya indirme bağlantısı gönderin</p>
        <p style="margin:0;font-size:13px;line-height:1.6;opacity:0.75;">
            Süre ve kullanım şeklini aşağıdan seçip “Bağlantı oluştur”a basın. Ardından çıkan “WhatsApp’ta gönder” butonu WhatsApp’ı hazır mesajla açar; bağlantıyı açan kişi giriş yapmadan Excel’i indirir.
        </p>
    </div>
</div>
