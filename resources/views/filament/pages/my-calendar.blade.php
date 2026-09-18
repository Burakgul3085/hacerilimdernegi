<x-filament-panels::page>
    @php
        $calendar = $this->calendarData;
        $weekdays = ['Pzt', 'Sal', 'Çar', 'Per', 'Cum', 'Cmt', 'Paz'];
    @endphp

    <div class="hc-cal space-y-5">
        <div class="hc-cal__hero">
            <div>
                <p class="hc-cal__eyebrow">Kişisel ajanda</p>
                <h2 class="hc-cal__title">{{ $calendar['label'] }}</h2>
                <p class="hc-cal__range">{{ $calendar['range'] }}</p>
            </div>
            <div class="hc-cal__stats">
                <div class="hc-cal__stat">
                    <span>{{ $calendar['stats']['today'] }}</span>
                    <small>Bugün</small>
                </div>
                <div class="hc-cal__stat">
                    <span>{{ $calendar['stats']['upcoming'] }}</span>
                    <small>7 gün</small>
                </div>
                <div class="hc-cal__stat hc-cal__stat--gold">
                    <span>{{ $calendar['stats']['pending'] }}</span>
                    <small>Hatırlatma</small>
                </div>
            </div>
        </div>

        <div class="hc-cal__toolbar">
            <div class="hc-cal__nav">
                <button type="button" wire:click="shiftPeriod(-1)" class="hc-cal__icon-btn" title="Önceki">‹</button>
                <button type="button" wire:click="goToday" class="hc-cal__text-btn">Bugün</button>
                <button type="button" wire:click="shiftPeriod(1)" class="hc-cal__icon-btn" title="Sonraki">›</button>
            </div>

            <div class="hc-cal__modes">
                <button type="button" wire:click="setViewMode('month')" @class(['hc-cal__mode', 'is-active' => $viewMode === 'month'])>Ay</button>
                <button type="button" wire:click="setViewMode('week')" @class(['hc-cal__mode', 'is-active' => $viewMode === 'week'])>Hafta</button>
                <button type="button" wire:click="setViewMode('agenda')" @class(['hc-cal__mode', 'is-active' => $viewMode === 'agenda'])>Ajanda</button>
            </div>

            <x-filament::button wire:click="openCreate" icon="heroicon-o-plus">
                Yeni not
            </x-filament::button>
        </div>

        @if ($viewMode === 'agenda')
            <div class="hc-cal__agenda">
                @forelse ($calendar['agenda'] as $entry)
                    <button type="button" class="hc-cal__agenda-item" wire:click="openEdit({{ $entry['id'] }})">
                        <div class="hc-cal__agenda-date">
                            <strong>{{ $entry['date_label'] }}</strong>
                            <span>{{ $entry['time_label'] }}</span>
                        </div>
                        <div class="hc-cal__agenda-body">
                            <h3>{{ $entry['title'] }}</h3>
                            @if (filled($entry['description']))
                                <p>{{ \Illuminate\Support\Str::limit($entry['description'], 120) }}</p>
                            @endif
                        </div>
                        @if ($entry['has_reminder'])
                            <span @class([
                                'hc-cal__badge',
                                'is-pending' => $entry['reminder_status'] === 'pending',
                                'is-sent' => $entry['reminder_status'] === 'sent',
                                'is-failed' => $entry['reminder_status'] === 'failed',
                            ])>{{ $entry['reminder_label'] }}</span>
                        @endif
                    </button>
                @empty
                    <div class="hc-cal__empty">
                        <p>Önümüzdeki günlerde not yok.</p>
                        <x-filament::button wire:click="openCreate" color="gray">İlk notunu ekle</x-filament::button>
                    </div>
                @endforelse
            </div>
        @else
            <div class="hc-cal__grid-wrap">
                <div class="hc-cal__weekdays">
                    @foreach ($weekdays as $weekday)
                        <div>{{ $weekday }}</div>
                    @endforeach
                </div>

                <div @class(['hc-cal__grid', 'hc-cal__grid--week' => $viewMode === 'week'])>
                    @foreach ($calendar['days'] as $day)
                        <div
                            @class([
                                'hc-cal__day',
                                'is-today' => $day['is_today'],
                                'is-outside' => $day['is_outside'] ?? false,
                            ])
                        >
                            <div class="hc-cal__day-head">
                                <button
                                    type="button"
                                    class="hc-cal__day-num"
                                    wire:click="openCreate('{{ $day['date'] }} 09:00:00')"
                                    title="Bu güne not ekle"
                                >{{ $day['day'] }}</button>
                                @if ($viewMode === 'week')
                                    <span class="hc-cal__day-name">{{ $day['weekday'] }}</span>
                                @endif
                            </div>

                            <div class="hc-cal__day-events">
                                @foreach (array_slice($day['entries'], 0, $viewMode === 'week' ? 8 : 3) as $entry)
                                    <button
                                        type="button"
                                        class="hc-cal__chip"
                                        wire:click="openEdit({{ $entry['id'] }})"
                                        title="{{ $entry['title'] }}"
                                    >
                                        <span class="hc-cal__chip-time">{{ $entry['time_label'] }}</span>
                                        <span class="hc-cal__chip-title">{{ $entry['title'] }}</span>
                                    </button>
                                @endforeach

                                @if (count($day['entries']) > ($viewMode === 'week' ? 8 : 3))
                                    <span class="hc-cal__more">+{{ count($day['entries']) - ($viewMode === 'week' ? 8 : 3) }} daha</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    @if ($formOpen)
        <div class="hc-cal-modal" wire:key="calendar-modal">
            <div class="hc-cal-modal__backdrop" wire:click="closeForm"></div>
            <div class="hc-cal-modal__panel" role="dialog" aria-modal="true">
                <div class="hc-cal-modal__head">
                    <div>
                        <p class="hc-cal__eyebrow">{{ $editingId ? 'Düzenle' : 'Yeni kayıt' }}</p>
                        <h3>{{ $editingId ? 'Takvim notu' : 'Not ekle' }}</h3>
                    </div>
                    <button type="button" class="hc-cal__icon-btn" wire:click="closeForm" aria-label="Kapat">×</button>
                </div>

                <form wire:submit="saveEntry" class="hc-cal-modal__body space-y-4">
                    {{ $this->form }}

                    <div class="hc-cal-modal__actions">
                        @if ($editingId)
                            <x-filament::button type="button" color="danger" wire:click="deleteEntry" wire:confirm="Bu not silinsin mi?">
                                Sil
                            </x-filament::button>
                        @endif
                        <div class="hc-cal-modal__actions-right">
                            <x-filament::button type="button" color="gray" wire:click="closeForm">Vazgeç</x-filament::button>
                            <x-filament::button type="submit">Kaydet</x-filament::button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <style>
        .hc-cal {
            --hc-ink: #2c2825;
            --hc-muted: #6b6560;
            --hc-header: #7a6b55;
            --hc-band: #f3eee4;
            --hc-paper: #fffcf8;
            --hc-line: #e6dfd3;
            --hc-gold: #8a7a62;
            color: var(--hc-ink);
        }

        .hc-cal__hero {
            display: flex;
            flex-wrap: wrap;
            gap: 1.25rem;
            justify-content: space-between;
            align-items: flex-end;
            padding: 1.35rem 1.5rem;
            border-radius: 1.25rem;
            background:
                radial-gradient(circle at top right, rgba(138, 122, 98, 0.28), transparent 42%),
                linear-gradient(135deg, #161513 0%, #3a342e 55%, #7a6b55 130%);
            color: #fffcf8;
            box-shadow: 0 18px 40px rgba(22, 21, 19, 0.18);
        }

        .hc-cal__eyebrow {
            margin: 0;
            font-size: 0.75rem;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            opacity: 0.72;
        }

        .hc-cal__title {
            margin: 0.2rem 0 0;
            font-size: clamp(1.6rem, 2vw, 2rem);
            font-weight: 700;
            line-height: 1.15;
        }

        .hc-cal__range {
            margin: 0.35rem 0 0;
            opacity: 0.78;
            font-size: 0.92rem;
        }

        .hc-cal__stats {
            display: flex;
            gap: 0.65rem;
        }

        .hc-cal__stat {
            min-width: 4.5rem;
            padding: 0.7rem 0.85rem;
            border-radius: 0.9rem;
            background: rgba(255, 252, 248, 0.1);
            border: 1px solid rgba(255, 252, 248, 0.14);
            text-align: center;
        }

        .hc-cal__stat span {
            display: block;
            font-size: 1.35rem;
            font-weight: 700;
        }

        .hc-cal__stat small {
            color: rgba(255, 252, 248, 0.72);
            font-size: 0.72rem;
        }

        .hc-cal__stat--gold {
            background: rgba(243, 238, 228, 0.18);
        }

        .hc-cal__toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            align-items: center;
            justify-content: space-between;
            padding: 0.85rem 1rem;
            border: 1px solid var(--hc-line);
            border-radius: 1rem;
            background: var(--hc-paper);
        }

        .hc-cal__nav,
        .hc-cal__modes {
            display: flex;
            gap: 0.4rem;
            align-items: center;
        }

        .hc-cal__icon-btn,
        .hc-cal__text-btn,
        .hc-cal__mode {
            border: 1px solid var(--hc-line);
            background: #fff;
            color: var(--hc-ink);
            border-radius: 0.7rem;
            padding: 0.45rem 0.75rem;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            transition: 0.15s ease;
        }

        .hc-cal__icon-btn {
            width: 2.2rem;
            height: 2.2rem;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }

        .hc-cal__mode.is-active,
        .hc-cal__icon-btn:hover,
        .hc-cal__text-btn:hover,
        .hc-cal__mode:hover {
            background: var(--hc-band);
            border-color: #d4cbbe;
        }

        .hc-cal__mode.is-active {
            background: var(--hc-header);
            border-color: var(--hc-header);
            color: #fffcf8;
        }

        .hc-cal__grid-wrap {
            border: 1px solid var(--hc-line);
            border-radius: 1.15rem;
            overflow: hidden;
            background: var(--hc-paper);
            box-shadow: 0 10px 30px rgba(44, 40, 37, 0.05);
        }

        .hc-cal__weekdays {
            display: grid;
            grid-template-columns: repeat(7, minmax(0, 1fr));
            background: var(--hc-header);
            color: #fffcf8;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .hc-cal__weekdays > div {
            padding: 0.7rem 0.5rem;
            text-align: center;
        }

        .hc-cal__grid {
            display: grid;
            grid-template-columns: repeat(7, minmax(0, 1fr));
        }

        .hc-cal__grid--week .hc-cal__day {
            min-height: 12rem;
        }

        .hc-cal__day {
            min-height: 7.5rem;
            border-top: 1px solid var(--hc-line);
            border-right: 1px solid var(--hc-line);
            padding: 0.45rem;
            background: #fff;
        }

        .hc-cal__day:nth-child(7n) {
            border-right: 0;
        }

        .hc-cal__day.is-outside {
            background: #faf7f2;
            color: #9a938a;
        }

        .hc-cal__day.is-today {
            background: linear-gradient(180deg, #f8f1e4 0%, #fff 55%);
            box-shadow: inset 0 0 0 2px rgba(122, 107, 85, 0.35);
        }

        .hc-cal__day-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 0.35rem;
        }

        .hc-cal__day-num {
            width: 1.85rem;
            height: 1.85rem;
            border: 0;
            border-radius: 999px;
            background: transparent;
            font-weight: 700;
            cursor: pointer;
        }

        .hc-cal__day.is-today .hc-cal__day-num {
            background: var(--hc-header);
            color: #fffcf8;
        }

        .hc-cal__day-name {
            font-size: 0.75rem;
            color: var(--hc-muted);
        }

        .hc-cal__day-events {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .hc-cal__chip {
            display: flex;
            gap: 0.35rem;
            width: 100%;
            text-align: left;
            border: 0;
            border-radius: 0.55rem;
            padding: 0.28rem 0.4rem;
            background: var(--hc-band);
            color: var(--hc-ink);
            cursor: pointer;
            overflow: hidden;
        }

        .hc-cal__chip:hover {
            background: #ebe4d8;
        }

        .hc-cal__chip-time {
            flex: 0 0 auto;
            font-size: 0.68rem;
            font-weight: 700;
            color: var(--hc-gold);
        }

        .hc-cal__chip-title {
            font-size: 0.72rem;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .hc-cal__more {
            font-size: 0.7rem;
            color: var(--hc-muted);
            padding-left: 0.2rem;
        }

        .hc-cal__agenda {
            display: flex;
            flex-direction: column;
            gap: 0.65rem;
        }

        .hc-cal__agenda-item {
            display: grid;
            grid-template-columns: 8rem 1fr auto;
            gap: 1rem;
            align-items: center;
            width: 100%;
            text-align: left;
            border: 1px solid var(--hc-line);
            border-radius: 1rem;
            padding: 1rem 1.1rem;
            background: var(--hc-paper);
            cursor: pointer;
            transition: 0.15s ease;
        }

        .hc-cal__agenda-item:hover {
            border-color: #d4cbbe;
            transform: translateY(-1px);
            box-shadow: 0 10px 24px rgba(44, 40, 37, 0.06);
        }

        .hc-cal__agenda-date strong {
            display: block;
            font-size: 0.92rem;
        }

        .hc-cal__agenda-date span {
            color: var(--hc-muted);
            font-size: 0.8rem;
        }

        .hc-cal__agenda-body h3 {
            margin: 0;
            font-size: 1rem;
            font-weight: 700;
        }

        .hc-cal__agenda-body p {
            margin: 0.25rem 0 0;
            color: var(--hc-muted);
            font-size: 0.875rem;
        }

        .hc-cal__badge {
            border-radius: 999px;
            padding: 0.3rem 0.65rem;
            font-size: 0.72rem;
            font-weight: 700;
            background: var(--hc-band);
            color: var(--hc-header);
        }

        .hc-cal__badge.is-pending { background: #efe6d4; }
        .hc-cal__badge.is-sent { background: #e5efe6; color: #2f5d3a; }
        .hc-cal__badge.is-failed { background: #f3e0de; color: #8a3b32; }

        .hc-cal__empty {
            border: 1px dashed var(--hc-line);
            border-radius: 1rem;
            padding: 2.5rem 1rem;
            text-align: center;
            background: var(--hc-paper);
            color: var(--hc-muted);
        }

        .hc-cal-modal {
            position: fixed;
            inset: 0;
            z-index: 80;
            display: grid;
            place-items: center;
            padding: 1rem;
        }

        .hc-cal-modal__backdrop {
            position: absolute;
            inset: 0;
            background: rgba(22, 21, 19, 0.45);
            backdrop-filter: blur(3px);
        }

        .hc-cal-modal__panel {
            position: relative;
            width: min(560px, 100%);
            max-height: min(90vh, 760px);
            overflow: auto;
            border-radius: 1.25rem;
            background: var(--hc-paper);
            border: 1px solid var(--hc-line);
            box-shadow: 0 28px 60px rgba(22, 21, 19, 0.28);
        }

        .hc-cal-modal__head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 1.15rem 1.25rem 0.5rem;
        }

        .hc-cal-modal__head h3 {
            margin: 0.15rem 0 0;
            font-size: 1.2rem;
            font-weight: 700;
        }

        .hc-cal-modal__body {
            padding: 0.5rem 1.25rem 1.25rem;
        }

        .hc-cal-modal__actions {
            display: flex;
            justify-content: space-between;
            gap: 0.75rem;
            align-items: center;
            padding-top: 0.5rem;
        }

        .hc-cal-modal__actions-right {
            display: flex;
            gap: 0.5rem;
            margin-left: auto;
        }

        @media (max-width: 900px) {
            .hc-cal__day { min-height: 5.5rem; }
            .hc-cal__chip-time { display: none; }
            .hc-cal__agenda-item {
                grid-template-columns: 1fr;
                gap: 0.45rem;
            }
        }
    </style>
</x-filament-panels::page>
