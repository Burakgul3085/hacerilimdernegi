<?php

namespace App\Http\Controllers;

use App\Enums\ApplicationStatus;
use App\Models\Event;
use App\Models\EventRegistration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EventController extends Controller
{
    public function index(): View
    {
        return view('pages.events.index', [
            'events' => Event::query()->published()->orderBy('starts_at')->paginate(9),
        ]);
    }

    public function show(Event $event): View
    {
        abort_unless($event->is_published, 404);

        return view('pages.events.show', compact('event'));
    }

    public function register(Request $request, Event $event): RedirectResponse
    {
        abort_unless($event->is_published && $event->registration_open, 404);

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
}
