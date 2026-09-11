@extends('layouts.app')

@section('title', 'Bağış')
@section('description', $settings['donate_intro'])

@section('content')

<x-page-header
    eyebrow="Destek"
    title="Bağış"
    :lead="$settings['donate_intro']"
    :breadcrumbs="[['label' => 'Bağış']]" />

<section class="border-b border-line bg-paper">
    <div class="shell">
        <div class="grid grid-cols-1 gap-px bg-line sm:grid-cols-3">
            <div class="donate-trust reveal bg-paper px-6 py-8 text-center sm:px-8" style="--reveal-delay: 0ms">
                <span class="donate-trust-icon inline-flex h-11 w-11 items-center justify-center rounded-full border border-line text-gold">
                    <x-ui.icon name="building" class="h-5 w-5" />
                </span>
                <p class="mt-4 font-display text-xl text-forest">Resmî hesap</p>
                <p class="mx-auto mt-2 max-w-[16rem] text-[13px] leading-relaxed text-muted">Bağışlar yalnızca dernek adına kayıtlı banka hesabına kabul edilir.</p>
            </div>
            <div class="donate-trust reveal bg-paper px-6 py-8 text-center sm:px-8" style="--reveal-delay: 80ms">
                <span class="donate-trust-icon inline-flex h-11 w-11 items-center justify-center rounded-full border border-line text-gold">
                    <x-ui.icon name="document" class="h-5 w-5" />
                </span>
                <p class="mt-4 font-display text-xl text-forest">Havale / EFT</p>
                <p class="mx-auto mt-2 max-w-[16rem] text-[13px] leading-relaxed text-muted">Kart ile tahsilat yoktur. İşlem bankanız üzerinden tamamlanır.</p>
            </div>
            <div class="donate-trust reveal bg-paper px-6 py-8 text-center sm:px-8" style="--reveal-delay: 160ms">
                <span class="donate-trust-icon inline-flex h-11 w-11 items-center justify-center rounded-full border border-line text-gold">
                    <x-ui.icon name="heart" class="h-5 w-5" />
                </span>
                <p class="mt-4 font-display text-xl text-forest">Faaliyetlere gider</p>
                <p class="mx-auto mt-2 max-w-[16rem] text-[13px] leading-relaxed text-muted">Katkınız ilim, sohbet ve kültür çalışmalarının sürmesine vesile olur.</p>
            </div>
        </div>
    </div>
</section>

<section class="shell py-14 lg:py-20">
    <div class="grid gap-10 lg:grid-cols-[minmax(0,1fr)_22rem] lg:items-start lg:gap-14">
        <div class="space-y-8">
            @if ($bankDetailsAreDemo)
                <div class="donate-alert reveal flex items-start gap-4 rounded-2xl border border-amber-500/70 bg-amber-50 px-5 py-5 text-amber-950 sm:px-6" role="alert">
                    <span class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-amber-500/40 bg-amber-100 text-amber-800">
                        <x-ui.icon name="shield" class="h-5 w-5" />
                    </span>
                    <div>
                        <p class="font-display text-2xl leading-snug">Demo banka bilgisi — ödeme yapmayınız</p>
                        <p class="mt-2 text-sm leading-relaxed">Gerçek banka ve IBAN bilgileri dernek yönetimi tarafından henüz bildirilmedi.</p>
                    </div>
                </div>
            @endif

            <article class="donate-certificate reveal relative overflow-hidden rounded-2xl border border-line bg-paper">
                <div class="donate-certificate-shine" aria-hidden="true"></div>
                <div class="grain pointer-events-none absolute inset-0 opacity-40"></div>

                <div class="relative px-6 py-8 sm:px-8 sm:py-10">
                    <div class="flex items-center justify-between gap-4">
                        <p class="eyebrow">Resmî hesap bilgisi</p>
                        <span class="flex h-11 w-11 items-center justify-center rounded-full border border-line bg-cream text-gold">
                            <x-ui.icon name="gift" class="h-5 w-5" />
                        </span>
                    </div>

                    <h2 class="mt-6 font-display text-3xl leading-snug text-forest sm:text-4xl">{{ $settings['bank_account_name'] ?: 'Hesap adı bekleniyor' }}</h2>

                    <dl class="mt-8 grid gap-6 sm:grid-cols-2">
                        <div>
                            <dt class="text-[11px] font-semibold uppercase tracking-[0.22em] text-gold">Banka</dt>
                            <dd class="mt-2 text-lg text-forest">{{ $settings['bank_name'] ?: '—' }}</dd>
                        </div>
                        @if (filled($settings['bank_branch'] ?? null))
                            <div>
                                <dt class="text-[11px] font-semibold uppercase tracking-[0.22em] text-gold">Şube</dt>
                                <dd class="mt-2 text-lg text-forest">{{ $settings['bank_branch'] }}</dd>
                            </div>
                        @endif
                    </dl>

                    <div class="donate-iban mt-8 rounded-2xl border border-line bg-cream px-5 py-5 sm:px-6"
                         x-data="{ copied: false }">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-gold">IBAN</p>
                            @if (filled($ibanCopy))
                                <button type="button"
                                        class="donate-copy"
                                        data-iban="{{ $ibanCopy }}"
                                        x-on:click="
                                            const value = $el.dataset.iban ?? '';
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
                                        Kopyala
                                    </span>
                                    <span class="inline-flex items-center gap-1.5" x-cloak x-show="copied">
                                        <x-ui.icon name="check" class="h-4 w-4" />
                                        Kopyalandı
                                    </span>
                                </button>
                            @endif
                        </div>
                        <p class="donate-iban-value mt-3 font-mono text-[1.05rem] leading-relaxed tracking-[0.08em] text-forest sm:text-xl">{{ $ibanDisplay ?: 'Panelden IBAN ekleyiniz' }}</p>
                    </div>

                    @if (filled($settings['donation_reference'] ?? null))
                        <p class="mt-6 text-sm leading-relaxed text-muted">{{ $settings['donation_reference'] }}</p>
                    @endif

                    @if (filled($settings['donation_note']))
                        <p class="mt-4 border-t border-line pt-5 text-sm leading-relaxed text-muted">{{ $settings['donation_note'] }}</p>
                    @endif
                </div>
            </article>

            <div class="reveal">
                <p class="eyebrow">Nasıl bağış yapılır</p>
                <ol class="mt-6 grid gap-4 sm:grid-cols-3">
                    <li class="donate-step card p-5">
                        <span class="donate-step-index">01</span>
                        <p class="mt-4 font-display text-xl text-forest">IBAN’ı kopyalayın</p>
                        <p class="mt-2 text-[13px] leading-relaxed text-muted">Yukarıdaki hesabı panoya alın; kart bilgisi istenmez.</p>
                    </li>
                    <li class="donate-step card p-5" style="--reveal-delay: 80ms">
                        <span class="donate-step-index">02</span>
                        <p class="mt-4 font-display text-xl text-forest">Havale veya EFT</p>
                        <p class="mt-2 text-[13px] leading-relaxed text-muted">Kendi bankanızdan dernek hesabına tutarı gönderin.</p>
                    </li>
                    <li class="donate-step card p-5" style="--reveal-delay: 160ms">
                        <span class="donate-step-index">03</span>
                        <p class="mt-4 font-display text-xl text-forest">Açıklama yazın</p>
                        <p class="mt-2 text-[13px] leading-relaxed text-muted">Dekont açıklamasına adınızı soyadınızı eklemeniz yeterlidir.</p>
                    </li>
                </ol>
            </div>

            @if (filled($donationPurposes))
                <div class="reveal">
                    @if (filled($settings['donation_purposes_title'] ?? null))
                        <p class="eyebrow">Kullanım</p>
                        <h2 class="mt-3 font-display text-3xl leading-snug text-forest">{{ $settings['donation_purposes_title'] }}</h2>
                    @endif

                    <ul class="mt-6 grid gap-4 sm:grid-cols-3">
                        @foreach ($donationPurposes as $purpose)
                            <li class="donate-purpose card p-5">
                                <span class="donate-purpose-icon flex h-11 w-11 items-center justify-center rounded-full border border-line text-gold">
                                    <x-ui.icon :name="$purpose['icon'] ?? 'gift'" class="h-5 w-5" />
                                </span>
                                <p class="mt-4 font-display text-xl text-forest">{{ $purpose['title'] ?? '' }}</p>
                                @if (filled($purpose['text'] ?? null))
                                    <p class="mt-2 text-[13px] leading-relaxed text-muted">{{ $purpose['text'] }}</p>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        <aside class="reveal space-y-4 lg:sticky lg:top-32">
            <div class="card grain p-7">
                <span class="flex h-12 w-12 items-center justify-center rounded-full border border-line bg-paper text-gold">
                    <x-ui.icon name="shield" class="h-6 w-6" />
                </span>
                <p class="mt-5 font-display text-2xl leading-snug text-forest">Online kart ödemesi yoktur</p>
                <p class="mt-3 text-sm leading-relaxed text-muted">
                    Bağışlar yalnızca yukarıdaki hesap bilgileriyle, banka havalesi veya EFT yoluyla kabul edilir.
                    Site üzerinden kart bilgisi istenmez.
                </p>
            </div>

            <div class="card p-7">
                <p class="eyebrow">Sorularınız için</p>
                <ul class="mt-4 space-y-3">
                    @if (filled($settings['phone']))
                        <li><x-meta icon="phone">{{ $settings['phone'] }}</x-meta></li>
                    @endif
                    @if (filled($settings['email']))
                        <li><x-meta icon="mail">{{ $settings['email'] }}</x-meta></li>
                    @endif
                </ul>
                <a href="{{ route('contact') }}" class="btn btn-outline btn-sm mt-6 w-full">İletişim formu</a>
                @if (filled($whatsappChatUrl ?? null))
                    <a href="{{ $whatsappChatUrl }}" class="btn btn-whatsapp btn-sm mt-3 w-full" target="_blank" rel="noopener noreferrer" data-no-veil>
                        WhatsApp
                        <x-ui.icon name="whatsapp" class="h-4 w-4" />
                    </a>
                @endif
            </div>
        </aside>
    </div>
</section>

@endsection
