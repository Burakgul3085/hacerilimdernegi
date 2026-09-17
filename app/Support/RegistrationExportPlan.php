<?php

namespace App\Support;

use App\Enums\ApplicationStatus;
use App\Models\Activity;
use App\Models\EventRegistration;

/**
 * Faaliyet formundan Excel sütun planını kurar. Hücreler soru kimliğine bağlanır, sütun konumuna değil.
 */
class RegistrationExportPlan
{
    /**
     * @return array<string, bool>
     */
    public static function defaultFixedColumns(): array
    {
        return [
            'submitted_at' => true,
            'name' => true,
            'email' => true,
            'phone' => true,
            'status' => true,
            'source' => true,
            'replied' => false,
            'kvkk' => false,
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function fixedLabels(): array
    {
        return [
            'submitted_at' => 'Başvuru tarihi',
            'name' => 'Ad soyad',
            'email' => 'E-posta',
            'phone' => 'Telefon',
            'status' => 'Durum',
            'source' => 'Kaynak',
            'replied' => 'Yanıt',
            'kvkk' => 'KVKK',
        ];
    }

    /**
     * @param  array<string, mixed>|null  $stored
     * @return array<string, bool>
     */
    public static function fixedSelection(?array $stored): array
    {
        $selection = self::defaultFixedColumns();

        if ($stored === null) {
            return $selection;
        }

        foreach ($selection as $key => $default) {
            if (array_key_exists($key, $stored)) {
                $selection[$key] = (bool) $stored[$key];
            }
        }

        return $selection;
    }

    /**
     * @return list<array{key: string, header: string}>
     */
    public static function columns(Activity $activity, bool $includeLegacyNotes = false): array
    {
        $columns = [
            ['key' => 'id', 'header' => 'Başvuru no'],
        ];

        foreach (self::fixedSelection($activity->excel_columns) as $key => $visible) {
            if (! $visible) {
                continue;
            }

            $columns[] = [
                'key' => $key,
                'header' => self::fixedLabels()[$key],
            ];
        }

        $currentKeys = [];

        foreach ($activity->registrationFieldDefinitions() as $field) {
            $currentKeys[] = $field['key'];

            if (($field['excel'] ?? true) === false) {
                continue;
            }

            $columns[] = [
                'key' => 'q:'.$field['key'],
                'header' => $field['label'],
            ];
        }

        foreach ($activity->excel_archived_questions ?? [] as $archived) {
            if (! is_array($archived) || empty($archived['include'])) {
                continue;
            }

            $key = trim((string) ($archived['key'] ?? ''));

            if ($key === '' || in_array($key, $currentKeys, true)) {
                continue;
            }

            $columns[] = [
                'key' => 'q:'.$key,
                'header' => trim((string) ($archived['label'] ?? $key)),
            ];
        }

        if ($includeLegacyNotes) {
            $columns[] = ['key' => 'legacy_notes', 'header' => 'Eski not'];
        }

        return $columns;
    }

    /**
     * @param  list<array{key: string, header: string}>  $columns
     * @return list<string>
     */
    public static function row(EventRegistration $registration, array $columns): array
    {
        return array_map(
            fn (array $column): string => self::value($registration, $column['key']),
            $columns,
        );
    }

    public static function value(EventRegistration $registration, string $key): string
    {
        if (str_starts_with($key, 'q:')) {
            return self::answer($registration, substr($key, 2));
        }

        return match ($key) {
            'id' => (string) $registration->getKey(),
            'submitted_at' => $registration->created_at?->timezone((string) config('app.timezone'))->format('d.m.Y H:i') ?? '',
            'name' => $registration->name,
            'email' => $registration->email,
            'phone' => (string) ($registration->phone ?? ''),
            'status' => $registration->status instanceof ApplicationStatus
                ? $registration->status->label()
                : (string) $registration->status,
            'source' => $registration->sourceLabel(),
            'replied' => $registration->replied_at ? 'Yanıtlandı' : 'Bekliyor',
            'kvkk' => $registration->kvkk_accepted ? 'Evet' : 'Hayır',
            'legacy_notes' => $registration->hasStructuredAnswers() ? '' : trim((string) $registration->notes),
            default => '',
        };
    }

    /**
     * Formdan çıkan soruları silmeden arşive alır. Forma geri giren soruyu arşivden çıkarır.
     *
     * @return list<array{key: string, label: string, include: bool}>
     */
    public static function mergeArchive(mixed $previousFields, mixed $nextFields, mixed $archive): array
    {
        $previous = self::fieldMap($previousFields);
        $next = self::fieldMap($nextFields);
        $kept = [];

        foreach (self::archiveItems($archive) as $item) {
            if (array_key_exists($item['key'], $next)) {
                continue;
            }

            $kept[$item['key']] = $item;
        }

        foreach ($previous as $key => $label) {
            if (array_key_exists($key, $next) || array_key_exists($key, $kept)) {
                continue;
            }

            $kept[$key] = [
                'key' => $key,
                'label' => $label,
                'include' => false,
            ];
        }

        return array_values($kept);
    }

    public static function needsLegacyNotes(EventRegistration $registration): bool
    {
        return ! $registration->hasStructuredAnswers() && filled(trim((string) $registration->notes));
    }

    public static function sheetName(string $title, array &$used): string
    {
        $clean = preg_replace('/[\\\\\\/\\?\\*\\[\\]:]/', ' ', $title) ?? $title;
        $clean = trim((string) preg_replace('/\s+/', ' ', $clean));

        if ($clean === '') {
            $clean = 'Faaliyet';
        }

        $base = mb_substr($clean, 0, 31);
        $name = $base;
        $suffix = 2;

        while (isset($used[mb_strtolower($name)])) {
            $tail = ' '.$suffix;
            $name = mb_substr($base, 0, 31 - mb_strlen($tail)).$tail;
            $suffix++;
        }

        $used[mb_strtolower($name)] = true;

        return $name;
    }

    private static function answer(EventRegistration $registration, string $key): string
    {
        foreach ($registration->answerItems() as $answer) {
            if (($answer['key'] ?? '') === $key) {
                return $answer['value'];
            }
        }

        return '';
    }

    /**
     * @return array<string, string>
     */
    private static function fieldMap(mixed $fields): array
    {
        if (! is_array($fields)) {
            return [];
        }

        $map = [];

        foreach (RegistrationForm::normalize($fields) as $field) {
            $map[$field['key']] = $field['label'];
        }

        return $map;
    }

    /**
     * @return list<array{key: string, label: string, include: bool}>
     */
    private static function archiveItems(mixed $archive): array
    {
        if (! is_array($archive)) {
            return [];
        }

        $items = [];

        foreach ($archive as $item) {
            if (! is_array($item)) {
                continue;
            }

            $key = trim((string) ($item['key'] ?? ''));

            if ($key === '') {
                continue;
            }

            $items[] = [
                'key' => $key,
                'label' => trim((string) ($item['label'] ?? $key)),
                'include' => (bool) ($item['include'] ?? false),
            ];
        }

        return $items;
    }
}
