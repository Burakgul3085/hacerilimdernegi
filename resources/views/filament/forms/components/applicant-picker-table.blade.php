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
    class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900"
    wire:ignore.self
>
    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-200 bg-[#fbf6ec] px-3 py-2.5 dark:border-gray-700 dark:bg-gray-800">
        <div class="text-sm font-medium text-[#161513] dark:text-gray-100">
            {{ count($applicants) }} başvuran
        </div>

        @if (! $isDisabled && $applicants !== [])
            <div class="flex items-center gap-3 text-sm">
                <button
                    type="button"
                    class="font-medium text-[#8a7a62] hover:underline"
                    x-show="! allSelected"
                    x-on:click="toggleAll()"
                >
                    Tümünü seç
                </button>
                <button
                    type="button"
                    class="font-medium text-[#8a7a62] hover:underline"
                    x-show="allSelected"
                    x-cloak
                    x-on:click="toggleAll()"
                >
                    Tüm seçimi kaldır
                </button>
            </div>
        @endif
    </div>

    <div class="max-h-80 overflow-auto">
        <table class="w-full min-w-[36rem] border-collapse text-sm">
            <thead class="sticky top-0 z-10 bg-[#161513] text-[#fffcf8]">
                <tr>
                    <th class="w-10 px-3 py-2.5 text-left font-medium"></th>
                    <th class="px-3 py-2.5 text-left font-medium">Ad soyad</th>
                    <th class="px-3 py-2.5 text-left font-medium">E-posta</th>
                    <th class="px-3 py-2.5 text-left font-medium">Telefon</th>
                    <th class="px-3 py-2.5 text-left font-medium">Durum</th>
                    <th class="px-3 py-2.5 text-right font-medium">No</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($applicants as $index => $applicant)
                    <tr @class([
                        'bg-white dark:bg-gray-900' => $index % 2 === 0,
                        'bg-[#fbf6ec]/40 dark:bg-gray-800/40' => $index % 2 === 1,
                    ])>
                        <td class="px-3 py-2.5 align-middle">
                            <input
                                type="checkbox"
                                class="fi-checkbox-input"
                                value="{{ $applicant['id'] }}"
                                @disabled($isDisabled)
                                x-bind:checked="isChecked(@js($applicant['id']))"
                                x-on:change="toggleOne(@js($applicant['id']))"
                            />
                        </td>
                        <td class="px-3 py-2.5 align-middle font-medium text-[#161513] dark:text-gray-100">
                            {{ $applicant['name'] }}
                        </td>
                        <td class="px-3 py-2.5 align-middle text-gray-600 dark:text-gray-300">
                            {{ $applicant['email'] }}
                        </td>
                        <td class="px-3 py-2.5 align-middle text-gray-600 dark:text-gray-300">
                            {{ $applicant['phone'] }}
                        </td>
                        <td class="px-3 py-2.5 align-middle">
                            <span class="inline-flex rounded-full bg-[#d4cbbe]/50 px-2 py-0.5 text-xs font-medium text-[#3a3733] dark:bg-gray-700 dark:text-gray-200">
                                {{ $applicant['status'] }}
                            </span>
                        </td>
                        <td class="px-3 py-2.5 align-middle text-right tabular-nums text-gray-500">
                            #{{ $applicant['id'] }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-3 py-8 text-center text-gray-500">
                            Bu listede başvuran yok.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($applicants !== [])
        <div class="border-t border-gray-200 px-3 py-2 text-xs text-gray-500 dark:border-gray-700">
            İstediğiniz satırları işaretleyin. Durum filtresi yalnızca seçimi günceller.
        </div>
    @endif
</div>
