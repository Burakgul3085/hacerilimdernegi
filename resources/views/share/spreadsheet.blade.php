<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $link ? 'Başvuru listesi' : 'Bağlantı geçersiz' }} — {{ $siteName }}</title>
    <style>
        :root { --ink: #161513; --gold: #8a7a62; --paper: #fffdf9; --canvas: #efe8dc; --line: #e7dccb; --muted: #6b6359; }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 32px 16px; background: var(--canvas); color: var(--ink); font-family: 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; }
        .shell { width: 100%; max-width: 460px; text-align: center; }
        .logo { width: 64px; height: 64px; border-radius: 50%; background: var(--paper); box-shadow: 0 0 0 1px var(--line); object-fit: contain; }
        .org { margin: 12px 0 22px; font-family: Georgia, 'Times New Roman', serif; font-size: 17px; }
        .card { background: var(--paper); border: 1px solid var(--line); border-top: 4px solid var(--gold); border-radius: 18px; padding: 32px 28px; text-align: left; box-shadow: 0 18px 40px -28px rgba(22, 21, 19, 0.35); }
        .eyebrow { display: inline-block; margin-bottom: 14px; padding: 5px 12px; border: 1px solid var(--line); border-radius: 999px; background: #f5eee2; color: var(--gold); font-size: 10px; font-weight: 700; letter-spacing: 0.18em; text-transform: uppercase; }
        h1 { margin: 0 0 8px; font-family: Georgia, 'Times New Roman', serif; font-size: 24px; line-height: 1.3; font-weight: 600; }
        .lead { margin: 0 0 20px; color: var(--muted); font-size: 15px; line-height: 1.65; }
        .facts { margin: 0 0 22px; padding: 14px 16px; border: 1px solid var(--line); border-radius: 14px; background: #f8f3ea; font-size: 14px; line-height: 1.8; }
        .facts strong { color: var(--ink); }
        .facts span { color: var(--muted); }
        button { width: 100%; padding: 15px 22px; border: 0; border-radius: 999px; background: var(--ink); color: #fffcf8; font: inherit; font-size: 15px; font-weight: 700; letter-spacing: 0.02em; cursor: pointer; }
        button:hover { background: #2b2825; }
        .note { margin: 18px 0 0; color: var(--muted); font-size: 12px; line-height: 1.6; }
    </style>
</head>
<body>
<main class="shell">
    <img class="logo" src="{{ $logoUrl }}" alt="{{ $siteName }}">
    <p class="org">{{ $siteName }}</p>

    <div class="card">
        @if ($link)
            <span class="eyebrow">Başvuru listesi</span>
            <h1>{{ $link->spreadsheet->title }}</h1>
            <p class="lead">Size gönderilen başvuru listesini Excel dosyası olarak indirebilirsiniz.</p>

            <div class="facts">
                <div><span>Satır sayısı:</span> <strong>{{ $link->spreadsheet->rows()->count() }}</strong></div>
                <div><span>Geçerlilik:</span> <strong>{{ $link->expires_at->timezone(config('app.timezone'))->translatedFormat('d F Y, H:i') }}</strong> tarihine kadar</div>
                @if ($link->single_use)
                    <div><span>Not:</span> <strong>Tek kullanımlık</strong>, ilk indirmeden sonra kapanır.</div>
                @endif
            </div>

            <form method="POST" action="{{ route('spreadsheet-share.download', ['token' => $token]) }}">
                @csrf
                <button type="submit">Excel dosyasını indir</button>
            </form>

            <p class="note">Bu dosya kişisel veri içerir. Lütfen yalnızca dernek çalışmaları için kullanın ve başkalarıyla paylaşmayın.</p>
        @else
            <span class="eyebrow">Bağlantı geçersiz</span>
            <h1>Bu bağlantı artık kullanılamıyor</h1>
            <p class="lead" style="margin-bottom:0;">Bağlantının süresi dolmuş, tek kullanımlık hakkı kullanılmış ya da gönderen kişi tarafından kapatılmış olabilir. Dosyaya hâlâ ihtiyacınız varsa gönderen kişiden yeni bir bağlantı isteyin.</p>
        @endif
    </div>
</main>
</body>
</html>
