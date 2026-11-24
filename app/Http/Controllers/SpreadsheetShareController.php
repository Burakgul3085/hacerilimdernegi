<?php

namespace App\Http\Controllers;

use App\Actions\ExportRegistrationSpreadsheetCsv;
use App\Models\RegistrationSpreadsheetShareLink;
use App\Support\MailTemplate;
use App\Support\SiteSettings;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * WhatsApp ile gönderilen süreli bağlantıdan e-tablo Excel'ini indirtir.
 * Bağlantı önizlemesi dosyayı tüketmesin diye indirme ayrı bir POST isteğidir.
 */
class SpreadsheetShareController extends Controller
{
    public function show(string $token): Response
    {
        $link = RegistrationSpreadsheetShareLink::findByToken($token);
        $link?->loadMissing('spreadsheet');

        $active = $link !== null && $link->spreadsheet !== null && $link->isActive();

        return response()
            ->view('share.spreadsheet', [
                'link' => $active ? $link : null,
                'token' => $token,
                'siteName' => (string) SiteSettings::get('site_name', 'Hâcer İlim ve Kültür Derneği'),
                'logoUrl' => MailTemplate::forBrowser('cid:hacer_mail_logo'),
            ], $active ? 200 : 410)
            ->header('X-Robots-Tag', 'noindex, nofollow')
            ->header('Referrer-Policy', 'no-referrer');
    }

    public function download(string $token, ExportRegistrationSpreadsheetCsv $export): StreamedResponse|Response
    {
        $link = DB::transaction(function () use ($token): ?RegistrationSpreadsheetShareLink {
            $link = RegistrationSpreadsheetShareLink::query()
                ->where('token_hash', RegistrationSpreadsheetShareLink::hashToken($token))
                ->lockForUpdate()
                ->first();

            if ($link === null || ! $link->isActive() || $link->spreadsheet === null) {
                return null;
            }

            $link->forceFill([
                'download_count' => $link->download_count + 1,
                'last_downloaded_at' => now(),
            ])->save();

            return $link;
        });

        if ($link === null) {
            return $this->show($token);
        }

        return $export->download($link->spreadsheet);
    }
}
