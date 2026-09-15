@extends('layouts.app')

@section('title', 'Yazılar ve şiirler')
@section('description', $settings['posts_intro'])

@section('content')

@php
    $openSubmitForm = $errors->any();
@endphp

<div
    x-data="{
        formOpen: {{ $openSubmitForm ? 'true' : 'false' }},
        openForm() {
            this.formOpen = true;
            history.replaceState(null, '', '#gonder');
        },
        closeForm() {
            this.formOpen = false;
            if (window.location.hash === '#gonder') {
                history.replaceState(null, '', window.location.pathname + window.location.search);
            }
        },
        init() {
            if (window.location.hash === '#gonder') {
                this.formOpen = true;
            }
            this.$watch('formOpen', (open) => {
                document.documentElement.classList.toggle('overflow-hidden', open);
            });
        },
    }"
    @keydown.escape.window="if (formOpen) closeForm()"
>

    <x-page-header
        eyebrow="Gündem"
        title="Yazılar ve şiirler"
        :lead="$settings['posts_intro']"
        :breadcrumbs="[['label' => 'Yazılar ve şiirler']]">
        <button type="button" class="btn btn-solid posts-submit-cta" @click="openForm()">
            <x-ui.icon name="document" class="h-4 w-4" />
            Yazı veya şiir gönder
        </button>
    </x-page-header>

    <section class="posts-stage">
        <div class="shell py-12 lg:py-16">
            <x-flash-status context="posts" class="mb-8" />

            <div class="reveal flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="posts-filters" role="navigation" aria-label="Yazı türü">
                    <a href="{{ route('posts.index') }}" class="chip {{ $currentType === '' ? 'chip-active' : '' }}">Tümü</a>
                    <a href="{{ route('posts.index', ['tur' => 'article']) }}" class="chip {{ $currentType === 'article' ? 'chip-active' : '' }}">Yazılar</a>
                    <a href="{{ route('posts.index', ['tur' => 'poem']) }}" class="chip {{ $currentType === 'poem' ? 'chip-active' : '' }}">Şiirler</a>
                </div>

                <button type="button" class="btn btn-outline posts-submit-cta-secondary sm:hidden" @click="openForm()">
                    <x-ui.icon name="document" class="h-4 w-4" />
                    Gönder
                </button>
            </div>

            @if ($posts->isEmpty())
                <x-empty-state class="mt-10 reveal" icon="document" title="Henüz yazı yok"
                               text="Yayınlanan yazı ve şiirler bu sayfada listelenir." />
            @else
                <div class="posts-grid mt-10 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($posts as $post)
                        <div class="reveal" style="--reveal-delay: {{ $loop->index * 70 }}ms">
                            <x-post-card :post="$post" />
                        </div>
                    @endforeach
                </div>

                <div class="mt-12">{{ $posts->links() }}</div>
            @endif

            <aside class="posts-invite reveal mt-14 overflow-hidden rounded-2xl border border-line bg-paper" style="--reveal-delay: 120ms">
                <div class="grid gap-0 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,0.9fr)]">
                    <div class="grain flex flex-col justify-center bg-cream px-6 py-8 sm:px-8 lg:px-10 lg:py-10">
                        <p class="eyebrow">Katılın</p>
                        <p class="mt-3 font-display text-3xl leading-[1.15] text-forest sm:text-4xl">Siz de yazın veya şiir gönderin</p>
                        <p class="mt-4 max-w-md text-sm leading-relaxed text-muted">Metniniz yönetime ulaşır; onaylandıktan sonra bu sayfada yayımlanır. Gönderimde ve onayda e-posta ile bilgilendirilirsiniz.</p>
                    </div>
                    <div class="flex flex-col justify-center gap-4 border-t border-line px-6 py-8 sm:px-8 lg:border-t-0 lg:border-l lg:px-10 lg:py-10">
                        <ul class="space-y-3">
                            <li><x-meta icon="check">Yazı veya şiir seçerek gönderin</x-meta></li>
                            <li><x-meta icon="check">Anında alındı e-postası</x-meta></li>
                            <li><x-meta icon="check">Onaylanınca sitede yayın</x-meta></li>
                        </ul>
                        <button type="button" class="btn btn-solid mt-2 w-fit" @click="openForm()">
                            Formu aç
                            <x-ui.icon name="arrow-right" class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            </aside>
        </div>
    </section>

    {{-- Gönderim formu: modal --}}
    <div
        x-show="formOpen"
        x-cloak
        x-transition.opacity.duration.200ms
        class="fixed inset-0 z-[70] flex items-end justify-center p-0 sm:items-center sm:p-6"
        role="dialog"
        aria-modal="true"
        aria-labelledby="posts-submit-title"
        id="gonder"
    >
        <div class="absolute inset-0 bg-forest-deep/55 backdrop-blur-[2px]" @click="closeForm()" aria-hidden="true"></div>

        <div
            x-show="formOpen"
            x-transition:enter="transition ease-out duration-250"
            x-transition:enter-start="translate-y-8 opacity-0 sm:translate-y-4 sm:scale-[0.98]"
            x-transition:enter-end="translate-y-0 opacity-100 sm:scale-100"
            x-transition:leave="transition ease-in duration-160"
            x-transition:leave-start="translate-y-0 opacity-100 sm:scale-100"
            x-transition:leave-end="translate-y-8 opacity-0 sm:translate-y-4 sm:scale-[0.98]"
            class="posts-submit-modal relative z-10 flex max-h-[min(94vh,44rem)] w-full max-w-3xl flex-col overflow-hidden rounded-t-3xl border border-line bg-paper shadow-lift sm:max-h-[min(88vh,44rem)] sm:rounded-3xl"
            @click.stop
        >
            <div class="flex items-start justify-between gap-4 border-b border-line bg-cream/70 px-5 py-4 sm:px-7 sm:py-5">
                <div class="min-w-0 pr-2">
                    <p class="eyebrow">Gönderi</p>
                    <h2 id="posts-submit-title" class="mt-1 font-display text-2xl leading-snug text-forest sm:text-3xl">Yazı veya şiir gönder</h2>
                    <p class="mt-2 text-sm leading-relaxed text-muted">Onay sonrası Yazılar ve şiirler sayfasında yayınlanır.</p>
                </div>
                <button type="button"
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-line bg-paper text-forest transition hover:border-gold hover:text-gold"
                        @click="closeForm()"
                        aria-label="Kapat">
                    <x-ui.icon name="close" class="h-5 w-5" />
                </button>
            </div>

            <form method="POST" action="{{ route('posts.store') }}" enctype="multipart/form-data" class="relative flex-1 space-y-5 overflow-y-auto overscroll-contain px-5 py-5 sm:px-7 sm:py-6">
                @csrf
                <x-honeypot />

                <x-field name="type" type="select" label="Tür" required :options="['article' => 'Yazı', 'poem' => 'Şiir']" placeholder="Yazı veya şiir seçin" />

                <div class="grid gap-5 sm:grid-cols-2">
                    <x-field name="name" label="Ad soyad" placeholder="Ad soyad" required autocomplete="name" />
                    <x-field name="email" type="email" label="E-posta" placeholder="E-posta" required autocomplete="email" />
                </div>

                <x-field name="title" label="Başlık" placeholder="Yazı veya şiir başlığı" required />

                <x-field name="body" type="textarea" label="Metin" rows="7"
                         placeholder="Yazınızı veya şiirinizi buraya yazın" required />

                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="flex flex-col gap-2">
                        <label for="field-cover" class="text-[13px] font-semibold text-forest">
                            Kapak resmi
                            <span class="font-medium text-muted"> (isteğe bağlı)</span>
                        </label>
                        <input id="field-cover" type="file" name="cover" accept="image/jpeg,image/png,image/webp,.jpg,.jpeg,.png,.webp"
                               class="field file:mr-3 file:rounded-lg file:border-0 file:bg-cream file:px-3 file:py-2 file:text-[12px] file:font-semibold file:text-forest">
                        <p class="text-[12px] leading-relaxed text-muted">JPG, PNG veya WebP. En fazla 5 MB. Zorunlu değil.</p>
                        @error('cover')
                            <p class="text-[13px] text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex flex-col gap-2">
                        <label for="field-photo" class="text-[13px] font-semibold text-forest">
                            Fotoğraf
                            <span class="font-medium text-muted"> (isteğe bağlı)</span>
                        </label>
                        <input id="field-photo" type="file" name="photo" accept="image/jpeg,image/png,image/webp,.jpg,.jpeg,.png,.webp"
                               class="field file:mr-3 file:rounded-lg file:border-0 file:bg-cream file:px-3 file:py-2 file:text-[12px] file:font-semibold file:text-forest">
                        <p class="text-[12px] leading-relaxed text-muted">Metne ek görsel. JPG, PNG veya WebP. En fazla 5 MB. Zorunlu değil.</p>
                        @error('photo')
                            <p class="text-[13px] text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <x-consent />

                <div class="flex flex-wrap items-center gap-3 border-t border-line pt-5">
                    <button type="submit" class="btn btn-solid">
                        Gönder
                        <x-ui.icon name="arrow-right" class="h-4 w-4" />
                    </button>
                    <button type="button" class="btn btn-outline" @click="closeForm()">Vazgeç</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
