<?php

namespace App\Http\Controllers\Admin;

use App\Actions\ExportRegistrationSpreadsheetCsv;
use App\Filament\Resources\RegistrationSpreadsheets\RegistrationSpreadsheetResource;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\RegistrationSpreadsheet;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Cihazın paylaşım menüsüne (WhatsApp vb.) eklenecek Excel dosyasını panel kullanıcısına verir.
 */
class SpreadsheetShareFileController extends Controller
{
    public function __invoke(RegistrationSpreadsheet $spreadsheet, ExportRegistrationSpreadsheetCsv $export): StreamedResponse
    {
        abort_unless(RegistrationSpreadsheetResource::canAccess(), 403);

        AuditLog::record('shared', RegistrationSpreadsheet::class, $spreadsheet->getKey(), $spreadsheet->title.' · dosya olarak');

        return $export->download($spreadsheet);
    }
}
