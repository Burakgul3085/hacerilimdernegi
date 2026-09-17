@php
    /** @var list<array{id: string, name: string, email: string, phone: string, status: string}> $applicants */
    $ids = array_column($applicants, 'id');
@endphp

<div
    x-data="{
        ids: {{ \Illuminate\Support\Js::from($ids) }},
        statePath: {{ \Illuminate\Support\Js::from($statePath) }},
        get selected() {
            return $wire.get(this.statePath) || []
        },
        set selected(value) {
            $wire.set(this.statePath, value)
        },
        get allSelected() {
            return this.ids.length > 0 && this.ids.every((id) => this.selected.map(String).includes(String(id)))
        },
        toggleAll() {
            this.selected = this.allSelected ? [] : [...this.ids]
        },
        toggleOne(id) {
            const current = this.selected.map(String)
            const value = String(id)
            this.selected = current.includes(value)
                ? current.filter((item) => item !== value)
                : [...current, value]
        },
        isChecked(id) {
            return this.selected.map(String).includes(String(id))
        },
    }"
    style="overflow:hidden;border:1px solid #e6dfd3;border-radius:12px;background:#fffcf8;"
    wire:ignore.self
>
    <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:8px;padding:12px 16px;background:#fbf6ec;border-bottom:1px solid #e6dfd3;">
        <div style="font-size:14px;font-weight:600;color:#161513;">
            {{ count($applicants) }} başvuran
        </div>

        @if (! $isDisabled && $applicants !== [])
            <div style="display:flex;align-items:center;gap:16px;font-size:13px;">
                <button
                    type="button"
                    style="font-weight:600;color:#8a7a62;background:none;border:0;cursor:pointer;padding:0;"
                    x-show="! allSelected"
                    x-on:click="toggleAll()"
                >
                    Tümünü seç
                </button>
                <button
                    type="button"
                    style="font-weight:600;color:#8a7a62;background:none;border:0;cursor:pointer;padding:0;"
                    x-show="allSelected"
                    x-cloak
                    x-on:click="toggleAll()"
                >
                    Tüm seçimi kaldır
                </button>
            </div>
        @endif
    </div>

    <div style="max-height:22rem;overflow:auto;">
        <table style="width:100%;min-width:720px;border-collapse:collapse;table-layout:fixed;font-size:13px;line-height:1.35;">
            <colgroup>
                <col style="width:44px">
                <col style="width:22%">
                <col style="width:30%">
                <col style="width:18%">
                <col style="width:14%">
                <col style="width:10%">
            </colgroup>
            <thead>
                <tr style="background:#161513;color:#fffcf8;">
                    <th style="padding:10px 12px;text-align:left;font-weight:600;"></th>
                    <th style="padding:10px 12px;text-align:left;font-weight:600;">Ad soyad</th>
                    <th style="padding:10px 12px;text-align:left;font-weight:600;">E-posta</th>
                    <th style="padding:10px 12px;text-align:left;font-weight:600;">Telefon</th>
                    <th style="padding:10px 12px;text-align:left;font-weight:600;">Durum</th>
                    <th style="padding:10px 12px;text-align:right;font-weight:600;">No</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($applicants as $index => $applicant)
                    <tr style="background:{{ $index % 2 === 0 ? '#ffffff' : '#fbf6ec' }};border-top:1px solid #ebe4d8;">
                        <td style="padding:10px 12px;vertical-align:middle;">
                            <input
                                type="checkbox"
                                class="fi-checkbox-input"
                                value="{{ $applicant['id'] }}"
                                @disabled($isDisabled)
                                x-bind:checked="isChecked(@js($applicant['id']))"
                                x-on:change="toggleOne(@js($applicant['id']))"
                            />
                        </td>
                        <td style="padding:10px 12px;vertical-align:middle;font-weight:600;color:#161513;word-break:break-word;">
                            {{ $applicant['name'] }}
                        </td>
                        <td style="padding:10px 12px;vertical-align:middle;color:#3a3733;word-break:break-word;">
                            {{ $applicant['email'] }}
                        </td>
                        <td style="padding:10px 12px;vertical-align:middle;color:#3a3733;white-space:nowrap;">
                            {{ $applicant['phone'] }}
                        </td>
                        <td style="padding:10px 12px;vertical-align:middle;">
                            <span style="display:inline-block;border-radius:999px;background:#d4cbbe66;color:#3a3733;padding:2px 10px;font-size:12px;font-weight:600;">
                                {{ $applicant['status'] }}
                            </span>
                        </td>
                        <td style="padding:10px 12px;vertical-align:middle;text-align:right;color:#6b6560;font-variant-numeric:tabular-nums;">
                            #{{ $applicant['id'] }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="padding:32px 12px;text-align:center;color:#6b6560;">
                            Bu listede başvuran yok.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($applicants !== [])
        <div style="border-top:1px solid #e6dfd3;padding:10px 16px;font-size:12px;color:#6b6560;background:#fffcf8;">
            İstediğiniz satırları işaretleyin. Durum filtresi yalnızca seçimi günceller.
        </div>
    @endif
</div>
