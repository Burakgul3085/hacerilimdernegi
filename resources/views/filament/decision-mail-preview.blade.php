<div style="display:flex;flex-direction:column;gap:14px;">
    <div style="border:1px solid rgba(120,113,108,0.25);border-radius:12px;padding:12px 16px;font-size:14px;line-height:1.6;">
        <div><span style="opacity:0.65;">Konu:</span> <strong>{{ $subject }}</strong></div>
        <div><span style="opacity:0.65;">Örnek alıcı:</span> {{ $recipient }}</div>
    </div>

    <iframe
        title="E-posta önizlemesi"
        srcdoc="{{ $html }}"
        style="display:block;width:100%;height:72vh;border:1px solid rgba(120,113,108,0.25);border-radius:12px;background:#efe8dc;"
        sandbox="allow-popups allow-popups-to-escape-sandbox"
    ></iframe>
</div>
