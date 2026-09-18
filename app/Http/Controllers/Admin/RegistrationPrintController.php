<?php

namespace App\Http\Controllers\Admin;

use App\Actions\PrintEventRegistrations;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RegistrationPrintController extends Controller
{
    public function __invoke(Request $request, string $token, PrintEventRegistrations $print): View
    {
        $payload = $print->payload($token);

        if ($payload === null) {
            throw new NotFoundHttpException('Yazdırma oturumu bulunamadı veya süresi doldu. Panelden yeniden yazdırın.');
        }

        if (($payload['user_id'] ?? null) !== null && (int) $payload['user_id'] !== (int) $request->user()?->getAuthIdentifier()) {
            throw new AccessDeniedHttpException('Bu yazdırma sayfası size ait değil.');
        }

        return view('prints.event-registrations', [
            'organization' => $payload['organization'],
            'documentKind' => $payload['document_kind'],
            'label' => $payload['label'],
            'generatedAt' => $payload['generated_at'],
            'sheets' => $payload['sheets'],
        ]);
    }
}
