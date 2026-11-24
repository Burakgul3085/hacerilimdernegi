@if ($links->isNotEmpty())
    <div style="margin-top:6px;">
        <p style="margin:0 0 8px;font-size:13px;font-weight:600;">Oluşturulan bağlantılar</p>
        <div style="display:flex;flex-direction:column;gap:8px;">
            @foreach ($links as $link)
                @php($active = $link->isActive())
                <div wire:key="share-link-{{ $link->id }}" style="display:flex;align-items:center;justify-content:space-between;gap:12px;border:1px solid rgba(120,113,108,0.25);border-radius:12px;padding:10px 14px;font-size:13px;line-height:1.5;">
                    <div>
                        <div>
                            <strong style="color:{{ $active ? '#1f8a4c' : 'inherit' }};">{{ $link->statusLabel() }}</strong>
                            · {{ $link->created_at->timezone(config('app.timezone'))->format('d.m.Y H:i') }}
                            @if ($link->user)
                                · {{ $link->user->name }}
                            @endif
                        </div>
                        <div style="opacity:0.7;">
                            {{ $link->single_use ? 'Tek kullanımlık' : 'Çok kullanımlık' }}
                            · {{ $link->download_count }} indirme
                            · Bitiş: {{ $link->expires_at->timezone(config('app.timezone'))->format('d.m.Y H:i') }}
                        </div>
                    </div>
                    @if ($active)
                        <x-filament::button size="sm" color="danger" outlined wire:click="revokeSpreadsheetShareLink({{ $link->id }})">
                            Bağlantıyı kapat
                        </x-filament::button>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
@endif
