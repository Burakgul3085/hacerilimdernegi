@props([
    'comments',
    'action',
    'place' => 'bu kaydın',
    'placeholder' => 'Düşünceniz',
])

<section id="yorumlar" class="mt-14 scroll-mt-28">
    <div class="mb-6 flex items-center gap-5">
        <h2 class="font-display text-2xl text-gold">Yorumlar</h2>
        <span class="rule flex-1"></span>
    </div>

    <x-flash-status context="content-comment" class="mb-6" />

    @if ($comments->isEmpty())
        <p class="text-sm leading-relaxed text-muted">Onaylanan yorumlar burada görünür.</p>
    @else
        <ol class="space-y-4">
            @foreach ($comments as $comment)
                <li class="card p-5">
                    <p class="font-display text-xl leading-snug text-forest">{{ $comment->publicName() }}</p>
                    <p class="mt-1 text-[13px] text-muted">{{ $comment->created_at?->timezone('Europe/Istanbul')->translatedFormat('d F Y') }}</p>
                    <p class="mt-3 whitespace-pre-wrap text-[15px] leading-relaxed text-muted">{{ $comment->body }}</p>
                </li>
            @endforeach
        </ol>
    @endif

    <form method="POST" action="{{ $action }}" class="card relative mt-8 space-y-5 p-6">
        @csrf
        <x-honeypot />
        <div>
            <p class="font-display text-2xl leading-snug text-forest">Yorum yaz</p>
            <p class="mt-2 text-sm leading-relaxed text-muted">Yorumunuz yönetici onayından sonra {{ $place }} altında yayınlanır. İsterseniz adınız sitede görünmez. Onaylandığında e-posta adresinize haber gider.</p>
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <x-field name="first_name" label="Ad" placeholder="Ad" required autocomplete="given-name" />
            <x-field name="last_name" label="Soyad" placeholder="Soyad" required autocomplete="family-name" />
        </div>

        <x-field name="email" type="email" label="E-posta" placeholder="E-posta" required autocomplete="email" />
        <x-field name="body" type="textarea" label="Yorum" rows="6" :placeholder="$placeholder" required />

        <label class="flex items-start gap-3 text-[13px] leading-relaxed text-muted">
            <input type="checkbox" name="hide_name" value="1" @checked(old('hide_name'))
                   class="mt-0.5 h-4 w-4 shrink-0 rounded border-line text-forest focus:ring-gold">
            <span>İsmimi sitede gizle</span>
        </label>

        <x-consent />

        <button type="submit" class="btn btn-solid btn-sm">Gönder</button>
    </form>
</section>
