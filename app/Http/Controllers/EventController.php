<?php

namespace App\Http\Controllers;

use App\Actions\ProcessEventRegistration;
use App\Enums\ApplicationStatus;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Support\FormGuard;
use App\Support\FormStatus;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EventController extends Controller
{
    public function index(Request $request): RedirectResponse
    {
        $month = $request->string('ay')->toString();

        return redirect()->route('programs.index', array_filter([
            'ay' => preg_match('/^\d{4}-\d{2}$/', $month) === 1 ? $month : null,
            'durum' => $request->string('durum')->toString() === 'gecmis' ? 'gecmis' : null,
        ]), 301);
    }

    public function show(Event $event): View
    {
        abort_unless($event->is_published, 404);

        $related = Event::query()
            ->published()
            ->whereKeyNot($event->getKey())
            ->where(fn (Builder $builder) => $builder->whereNull('starts_at')->orWhere('starts_at', '>=', now()->subDay()))
            ->orderBy('starts_at')
            ->limit(4)
            ->get();

        return view('pages.events.show', [
            'event' => $event,
            'related' => $related,
            'calendarUrl' => $this->calendarUrl($event),
        ]);
    }

    public function register(Request $request, Event $event): RedirectResponse
    {
        abort_unless($event->is_published && $event->registration_open, 404);

        if (FormGuard::isBot($request)) {
            return FormStatus::redirect('Katılım başvurunuz alındı. Size de bir onay e-postası gönderdik.', 'event');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:180'],
            'phone' => ['nullable', 'string', 'max:40'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'kvkk_accepted' => ['accepted'],
        ]);

        $registration = EventRegistration::query()->create([
            ...$data,
            'event_id' => $event->id,
            'kvkk_accepted' => true,
            'status' => ApplicationStatus::Pending,
        ]);

        app(ProcessEventRegistration::class)->handle($registration);

        return FormStatus::redirect('Katılım başvurunuz alındı. Size de bir onay e-postası gönderdik.', 'event');
    }

    /**
     * Etkinliği Google Takvim'e eklemek için hazır bağlantı üretir.
     */
    private function calendarUrl(Event $event): ?string
    {
        if (! $event->starts_at) {
            return null;
        }

        $start = $event->starts_at->clone()->utc();
        $end = ($event->ends_at ?? $event->starts_at->clone()->addHours(2))->clone()->utc();

        return 'https://calendar.google.com/calendar/render?'.http_build_query([
            'action' => 'TEMPLATE',
            'text' => $event->title,
            'dates' => $start->format('Ymd\THis\Z').'/'.$end->format('Ymd\THis\Z'),
            'location' => $event->location,
            'details' => strip_tags((string) $event->description),
        ]);
    }
}
