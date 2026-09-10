<?php

namespace App\Http\Controllers;

use App\Enums\ApplicationStatus;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Support\FormGuard;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EventController extends Controller
{
    public function index(Request $request): View
    {
        $month = $request->string('ay')->toString();

        $events = Event::query()
            ->published()
            ->when($month !== '' && preg_match('/^\d{4}-\d{2}$/', $month) === 1, function (Builder $query) use ($month): void {
                [$year, $monthNumber] = explode('-', $month);

                $query->whereYear('starts_at', $year)->whereMonth('starts_at', $monthNumber);
            }, function (Builder $query): void {
                $query->where(fn (Builder $builder) => $builder
                    ->whereNull('starts_at')
                    ->orWhere('starts_at', '>=', now()->subDay()));
            })
            ->orderBy('starts_at')
            ->get();

        return view('pages.events.index', [
            'grouped' => $events->groupBy(fn (Event $event) => $event->starts_at?->translatedFormat('F Y') ?: 'Tarihi belirlenecek'),
            'monthOptions' => $this->monthOptions(),
            'currentMonth' => $month,
        ]);
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
            return back()->with('status', 'Katılım başvurunuz alındı. En kısa sürede dönüş yapılacaktır.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:180'],
            'phone' => ['nullable', 'string', 'max:40'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'kvkk_accepted' => ['accepted'],
        ]);

        EventRegistration::query()->create([
            ...$data,
            'event_id' => $event->id,
            'kvkk_accepted' => true,
            'status' => ApplicationStatus::Pending,
        ]);

        return back()->with('status', 'Katılım başvurunuz alındı. En kısa sürede dönüş yapılacaktır.');
    }

    /**
     * Takvim filtresi için yaklaşan etkinliklerin bulunduğu aylar.
     *
     * @return array<string, string>
     */
    private function monthOptions(): array
    {
        return Event::query()
            ->published()
            ->whereNotNull('starts_at')
            ->where('starts_at', '>=', now()->startOfMonth())
            ->orderBy('starts_at')
            ->get()
            ->mapWithKeys(fn (Event $event) => [$event->starts_at->format('Y-m') => $event->starts_at->translatedFormat('F Y')])
            ->all();
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
