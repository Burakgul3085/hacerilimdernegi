<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class RegistrationForm
{
    public const TYPES = [
        'text',
        'textarea',
        'select',
        'checkbox',
        'number',
        'date',
        'email',
        'phone',
    ];

    /**
     * @return array<string, string>
     */
    public static function typeOptions(): array
    {
        return [
            'text' => 'Kısa metin',
            'textarea' => 'Uzun metin',
            'number' => 'Sayı',
            'date' => 'Tarih',
            'email' => 'E-posta',
            'phone' => 'Telefon',
            'select' => 'Seçim listesi',
            'checkbox' => 'Evet / hayır',
        ];
    }

    /**
     * Sistem alanları her formda sabittir; panelden düzenlenmez.
     *
     * @return list<array{key: string, label: string}>
     */
    public static function systemFields(): array
    {
        return [
            ['key' => 'name', 'label' => 'Ad soyad'],
            ['key' => 'email', 'label' => 'E-posta'],
            ['key' => 'phone', 'label' => 'Telefon'],
        ];
    }

    /**
     * Kayıt alanı tanımlanmamış eski faaliyetler için varsayılan “Not” alanı.
     *
     * @return list<array{key: string, label: string, type: string, required: bool, help: string, placeholder: string, options: list<string>}>
     */
    public static function defaultFields(): array
    {
        return [
            [
                'key' => 'notes',
                'label' => 'Not',
                'type' => 'textarea',
                'required' => false,
                'help' => '',
                'placeholder' => 'Eklemek istedikleriniz',
                'options' => [],
            ],
        ];
    }

    /**
     * @return list<array{key: string, label: string, type: string, required: bool, help: string, placeholder: string, options: list<string>}>
     */
    public static function normalize(mixed $fields): array
    {
        if (! is_array($fields) || $fields === []) {
            return self::defaultFields();
        }

        $normalized = [];
        $usedKeys = [];

        foreach ($fields as $index => $field) {
            if (! is_array($field)) {
                continue;
            }

            $label = trim((string) ($field['label'] ?? ''));

            if ($label === '') {
                continue;
            }

            $type = strtolower(trim((string) ($field['type'] ?? 'text')));

            if (! in_array($type, self::TYPES, true)) {
                $type = 'text';
            }

            $key = self::uniqueKey(
                filled($field['key'] ?? null) ? (string) $field['key'] : $label,
                $usedKeys,
                $index,
            );
            $usedKeys[] = $key;

            $options = self::normalizeOptions($field['options'] ?? [], $type);

            if ($type === 'select' && $options === []) {
                $type = 'text';
            }

            $normalized[] = [
                'key' => $key,
                'label' => Str::limit($label, 120, ''),
                'type' => $type,
                'required' => (bool) ($field['required'] ?? false),
                'help' => Str::limit(trim((string) ($field['help'] ?? '')), 280, ''),
                'placeholder' => Str::limit(trim((string) ($field['placeholder'] ?? '')), 120, ''),
                'options' => $options,
            ];
        }

        return $normalized === [] ? self::defaultFields() : $normalized;
    }

    /**
     * @param  list<array{key: string, label: string, type: string, required: bool, help: string, placeholder: string, options: list<string>}>  $fields
     * @return array<string, list<mixed>>
     */
    public static function validationRules(array $fields): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:180'],
            'phone' => ['nullable', 'string', 'max:40'],
            'kvkk_accepted' => ['accepted'],
        ];

        foreach ($fields as $field) {
            $name = 'custom.'.$field['key'];
            $required = $field['required'];

            $rules[$name] = match ($field['type']) {
                'checkbox' => $required ? ['accepted'] : ['nullable'],
                'select' => [
                    $required ? 'required' : 'nullable',
                    'string',
                    'max:255',
                    Rule::in($field['options']),
                ],
                'textarea' => [$required ? 'required' : 'nullable', 'string', 'max:2000'],
                'number' => [$required ? 'required' : 'nullable', 'numeric'],
                'date' => [$required ? 'required' : 'nullable', 'date'],
                'email' => [$required ? 'required' : 'nullable', 'email', 'max:180'],
                'phone' => [$required ? 'required' : 'nullable', 'string', 'max:40'],
                default => [$required ? 'required' : 'nullable', 'string', 'max:500'],
            };
        }

        return $rules;
    }

    /**
     * @param  list<array{key: string, label: string, type: string, required: bool, help: string, placeholder: string, options: list<string>}>  $fields
     * @param  array<string, mixed>  $input
     * @return list<array{key: string, label: string, value: string}>
     */
    public static function collectAnswers(array $fields, array $input): array
    {
        $custom = is_array($input['custom'] ?? null) ? $input['custom'] : [];
        $answers = [];

        foreach ($fields as $field) {
            $raw = $custom[$field['key']] ?? null;

            $value = match ($field['type']) {
                'checkbox' => filter_var($raw, FILTER_VALIDATE_BOOLEAN) || $raw === '1' || $raw === 1 || $raw === true
                    ? 'Evet'
                    : 'Hayır',
                'date' => self::formatDateValue($raw),
                'number' => filled($raw) || $raw === 0 || $raw === '0'
                    ? (string) $raw
                    : '',
                default => trim((string) ($raw ?? '')),
            };

            if ($field['type'] !== 'checkbox' && $value === '' && ! $field['required']) {
                continue;
            }

            if ($field['type'] === 'checkbox' && ! $field['required'] && $value === 'Hayır' && blank($raw)) {
                continue;
            }

            $answers[] = [
                'key' => $field['key'],
                'label' => $field['label'],
                'value' => $value,
            ];
        }

        return $answers;
    }

    /**
     * Liste ve eski not kolonunda hızlı okuma için kısa özet.
     *
     * @param  list<array{key: string, label: string, value: string}>  $answers
     */
    public static function notesSummary(array $answers): ?string
    {
        if ($answers === []) {
            return null;
        }

        if (count($answers) === 1 && ($answers[0]['key'] ?? '') === 'notes') {
            return $answers[0]['value'];
        }

        return collect($answers)
            ->map(fn (array $answer): string => $answer['label'].': '.$answer['value'])
            ->implode("\n");
    }

    /**
     * @param  list<array{key?: string, label: string, value: string}>  $answers
     */
    public static function previewSummary(array $answers, int $limit = 90): string
    {
        if ($answers === []) {
            return '';
        }

        $parts = collect($answers)
            ->take(2)
            ->map(fn (array $answer): string => $answer['label'].': '.$answer['value'])
            ->all();

        return Str::limit(implode(' · ', $parts), $limit);
    }

    /**
     * HTML input type for public forms.
     */
    public static function inputType(string $type): string
    {
        return match ($type) {
            'number' => 'number',
            'date' => 'date',
            'email' => 'email',
            'phone' => 'tel',
            default => 'text',
        };
    }

    /**
     * @param  list<string>  $usedKeys
     */
    private static function uniqueKey(string $source, array $usedKeys, int $index): string
    {
        $base = Str::slug($source, '_');

        if ($base === '' || in_array($base, ['name', 'email', 'phone', 'kvkk_accepted', 'custom'], true)) {
            $base = 'alan_'.($index + 1);
        }

        $key = $base;
        $suffix = 2;

        while (in_array($key, $usedKeys, true)) {
            $key = $base.'_'.$suffix;
            $suffix++;
        }

        return $key;
    }

    /**
     * @return list<string>
     */
    private static function normalizeOptions(mixed $options, string $type): array
    {
        if ($type !== 'select') {
            return [];
        }

        if (is_string($options)) {
            $options = preg_split("/\r\n|\n|\r/", $options) ?: [];
        }

        if (! is_array($options)) {
            return [];
        }

        $normalized = [];

        foreach ($options as $option) {
            $option = trim((string) $option);

            if ($option === '') {
                continue;
            }

            $normalized[] = Str::limit($option, 120, '');
        }

        return array_values(array_unique($normalized));
    }

    private static function formatDateValue(mixed $raw): string
    {
        $value = trim((string) ($raw ?? ''));

        if ($value === '') {
            return '';
        }

        try {
            return Carbon::parse($value)->translatedFormat('d F Y');
        } catch (\Throwable) {
            return $value;
        }
    }
}
