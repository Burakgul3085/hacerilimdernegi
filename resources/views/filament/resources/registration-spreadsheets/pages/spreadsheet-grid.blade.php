@php
    /** @var \App\Filament\Resources\RegistrationSpreadsheets\Pages\EditRegistrationSpreadsheet $this */
    $meta = method_exists($this, 'gridMeta') ? $this->gridMeta() : ['headers' => [], 'columnKeys' => [], 'statusOptions' => []];
    $headers = $meta['headers'];
    $columnKeys = $meta['columnKeys'];
    $statusOptions = $meta['statusOptions'];
@endphp

<div
    style="overflow:auto;border:1px solid #e6dfd3;border-radius:12px;background:#fffcf8;"
    wire:ignore.self
>
    <div style="padding:12px 16px;background:#f3eee4;border-bottom:1px solid #e6dfd3;font-size:13px;color:#6b6560;">
        {{ count($this->grid) }} satır · Kaydetince durum ve not panele yazılır
    </div>

    <div style="max-height:70vh;overflow:auto;">
        <table style="width:100%;min-width:720px;border-collapse:collapse;font-size:13px;">
            <thead>
                <tr style="background:#7a6b55;color:#fffcf8;">
                    @foreach ($headers as $header)
                        <th style="padding:10px 12px;text-align:left;font-weight:600;white-space:nowrap;">{{ $header }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse ($this->grid as $rowIndex => $row)
                    <tr style="background:{{ $rowIndex % 2 === 0 ? '#ffffff' : '#fbf6ec' }};border-top:1px solid #ebe4d8;">
                        @foreach ($columnKeys as $key)
                            <td style="padding:8px 10px;vertical-align:top;min-width:8rem;">
                                @if ($key === 'status')
                                    <select
                                        wire:model.live="grid.{{ $rowIndex }}.cells.{{ $key }}"
                                        style="width:100%;border:1px solid #e6dfd3;border-radius:8px;padding:6px 8px;background:#fff;"
                                    >
                                        @foreach ($statusOptions as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                @elseif (in_array($key, ['legacy_notes', 'notes'], true))
                                    <textarea
                                        wire:model.blur="grid.{{ $rowIndex }}.cells.{{ $key }}"
                                        rows="2"
                                        style="width:100%;border:1px solid #e6dfd3;border-radius:8px;padding:6px 8px;resize:vertical;"
                                    ></textarea>
                                @elseif ($key === 'id')
                                    <span style="font-variant-numeric:tabular-nums;color:#6b6560;">{{ $row['cells'][$key] ?? '' }}</span>
                                @else
                                    <input
                                        type="text"
                                        wire:model.blur="grid.{{ $rowIndex }}.cells.{{ $key }}"
                                        style="width:100%;border:1px solid #e6dfd3;border-radius:8px;padding:6px 8px;"
                                    />
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ max(1, count($headers)) }}" style="padding:28px 12px;text-align:center;color:#6b6560;">
                            Bu e-tabloda satır yok.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
