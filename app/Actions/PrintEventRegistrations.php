<?php

namespace App\Actions;

use App\Enums\ApplicationStatus;
use App\Models\Activity;
use App\Models\AuditLog;
use App\Models\EventRegistration;
use App\Support\HacerXlsxTemplate;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Seçilen başvuruları tarayıcıda yazdırılacak kurumsal sayfa için kısa ömürlü jeton üretir.
 */
class PrintEventRegistrations
{
    public const CACHE_PREFIX = 'registration-print:';

    public const TTL_MINUTES = 45;

    public function __construct(private ExportEventRegistrations $export) {}

    /**
     * @param  list<int|string>|null  $registrationIds
     * @param  list<string>|null  $columnKeys
     */
    public function issueToken(
        ?Activity $activity = null,
        ?ApplicationStatus $status = null,
        bool $unassignedOnly = false,
        ?array $registrationIds = null,
        ?array $columnKeys = null,
        ?int $userId = null,
    ): string {
        $plan = $this->export->plan($activity, $status, $unassignedOnly, $registrationIds, $columnKeys);

        AuditLog::record(
            'printed',
            $activity === null ? EventRegistration::class : Activity::class,
            $activity?->getKey(),
            $plan['label'],
            ['rows' => $plan['row_count']],
        );

        $token = (string) Str::uuid();

        Cache::put(self::CACHE_PREFIX.$token, [
            'user_id' => $userId,
            'label' => $plan['label'],
            'generated_at' => HacerXlsxTemplate::generatedAtLabel(),
            'organization' => HacerXlsxTemplate::ORGANIZATION,
            'document_kind' => HacerXlsxTemplate::DOCUMENT_KIND,
            'sheets' => $plan['sheets'],
        ], now()->addMinutes(self::TTL_MINUTES));

        return $token;
    }

    /**
     * @return array{user_id: int|null, label: string, generated_at: string, organization: string, document_kind: string, sheets: list<array{name: string, context?: string|null, summary?: string|null, rows: list<list<string>>}>}|null
     */
    public function payload(string $token): ?array
    {
        $payload = Cache::get(self::CACHE_PREFIX.$token);

        return is_array($payload) ? $payload : null;
    }
}
