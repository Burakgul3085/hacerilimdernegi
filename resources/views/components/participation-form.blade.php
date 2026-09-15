@props([
    'action',
    'context',
    'fields' => null,
    'title' => 'Katılım başvurusu',
    'lead' => 'Formu doldurun, dernek yönetimi sizinle iletişime geçsin.',
])

@php
    $isDynamic = $fields !== null;
    /** @var list<array{key: string, label: string, type: string, required: bool, options: list<string>}> $definitions */
    $definitions = $isDynamic
        ? \App\Support\RegistrationForm::normalize($fields)
        : [];
@endphp

<div {{ $attributes->class(['reveal mt-16 overflow-hidden rounded-2xl border border-line bg-paper']) }} id="kayit">
    <div class="grid lg:grid-cols-[20rem_minmax(0,1fr)]">
        <div class="grain flex flex-col justify-center bg-cream p-6 sm:p-8 lg:p-10">
            <span class="flex h-12 w-12 items-center justify-center rounded-full border border-line bg-paper text-gold">
                <x-ui.icon name="hand" class="h-6 w-6" />
            </span>
            <p class="mt-5 font-display text-3xl leading-snug text-forest">{{ $title }}</p>
            <p class="mt-3 text-sm leading-relaxed text-muted">{{ $lead }}</p>
        </div>

        <form method="POST" action="{{ $action }}" class="relative space-y-5 p-5 sm:p-8 lg:p-10">
            @csrf
            <x-honeypot />
            <x-flash-status :context="$context" />

            <div class="grid gap-5 sm:grid-cols-2">
                <x-field name="name" label="Ad soyad" placeholder="Ad soyad" required autocomplete="name" />
                <x-field name="email" type="email" label="E-posta" placeholder="E-posta" required autocomplete="email" />
                <x-field name="phone" label="Telefon" placeholder="Telefon" autocomplete="tel" class="sm:col-span-2" />
            </div>

            @if ($isDynamic)
                <div class="space-y-5">
                    @foreach ($definitions as $field)
                        @php
                            $inputName = 'custom['.$field['key'].']';
                            $selectOptions = collect($field['options'])
                                ->mapWithKeys(fn (string $option): array => [$option => $option])
                                ->all();
                        @endphp

                        @if ($field['type'] === 'checkbox')
                            @php
                                $checkboxId = 'field-custom-'.$field['key'];
                                $checked = (bool) old('custom.'.$field['key']);
                            @endphp
                            <div class="flex flex-col gap-2">
                                <label for="{{ $checkboxId }}" class="inline-flex items-start gap-3 text-sm text-forest">
                                    <input
                                        id="{{ $checkboxId }}"
                                        type="checkbox"
                                        name="{{ $inputName }}"
                                        value="1"
                                        @checked($checked)
                                        @if ($field['required']) required @endif
                                        class="mt-1 rounded border-line text-forest focus:ring-gold"
                                    >
                                    <span>
                                        {{ $field['label'] }}
                                        @if ($field['required'])
                                            <span class="text-gold"> *</span>
                                        @endif
                                    </span>
                                </label>
                                @error('custom.'.$field['key'])
                                    <p class="text-[13px] text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        @elseif ($field['type'] === 'select')
                            <x-field
                                :name="$inputName"
                                type="select"
                                :label="$field['label']"
                                :required="$field['required']"
                                :options="$selectOptions"
                                placeholder="Seçiniz"
                            />
                        @elseif ($field['type'] === 'textarea')
                            <x-field
                                :name="$inputName"
                                type="textarea"
                                :label="$field['label']"
                                :required="$field['required']"
                                :rows="4"
                                :placeholder="$field['key'] === 'notes' ? 'Eklemek istedikleriniz' : null"
                            />
                        @else
                            <x-field
                                :name="$inputName"
                                type="text"
                                :label="$field['label']"
                                :required="$field['required']"
                            />
                        @endif
                    @endforeach
                </div>
            @else
                <x-field name="notes" type="textarea" label="Not" rows="4" placeholder="Eklemek istedikleriniz" />
            @endif

            <x-consent />

            <button type="submit" class="btn btn-solid">
                Başvuruyu gönder
                <x-ui.icon name="arrow-right" class="h-4 w-4" />
            </button>
        </form>
    </div>
</div>
