<?php

namespace App\Actions;

use App\Models\AuditLog;
use App\Models\RegistrationSpreadsheet;
use App\Support\HacerXlsxTemplate;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * E-tabloyu program kayıtlarıyla aynı kurumsal yazdırma sayfasına açar.
 */
class PrintRegistrationSpreadsheet
{
    public function __construct(private ExportRegistrationSpreadsheetCsv $export) {}

    /**
     * @param  list<array{id?: int|string, cells: array<string, string>}>|null  $gridRows
     */
    public function issueToken(
        RegistrationSpreadsheet $spreadsheet,
        ?array $gridRows = null,
        ?int $userId = null,
    ): string {
        $plan = $this->export->plan($spreadsheet, $gridRows);

        AuditLog::record(
            'printed',
            RegistrationSpreadsheet::class,
            $spreadsheet->getKey(),
            $plan['label'],
            ['rows' => $plan['row_count']],
        );

        $token = (string) Str::uuid();

        Cache::put(PrintEventRegistrations::CACHE_PREFIX.$token, [
            'user_id' => $userId,
            'label' => $plan['label'],
            'generated_at' => HacerXlsxTemplate::generatedAtLabel(),
            'organization' => HacerXlsxTemplate::ORGANIZATION,
            'document_kind' => HacerXlsxTemplate::DOCUMENT_KIND,
            'sheets' => $plan['sheets'],
        ], now()->addMinutes(PrintEventRegistrations::TTL_MINUTES));

        return $token;
    }
}
