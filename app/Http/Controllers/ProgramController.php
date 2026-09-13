<?php

namespace App\Http\Controllers;

use App\Actions\ProcessEventRegistration;
use App\Enums\ApplicationStatus;
use App\Models\EventRegistration;
use App\Models\Program;
use App\Support\FormGuard;
use App\Support\FormStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProgramController extends Controller
{
    public function __construct(private ProcessEventRegistration $processEventRegistration) {}

    public function index(): RedirectResponse
    {
        return redirect()->route('activities.index', [], 301);
    }

    public function show(Program $program): View
    {
        abort_unless($program->is_published, 404);

        $program->load('activity');

        $related = Program::query()
            ->published()
            ->upcoming()
            ->whereKeyNot($program->getKey())
            ->where('type', $program->type)
            ->limit(4)
            ->get();

        return view('pages.programs.show', compact('program', 'related'));
    }

    public function register(Request $request, Program $program): RedirectResponse
    {
        abort_unless($program->is_published, 404);

        if (FormGuard::isBot($request)) {
            return FormStatus::redirect('Katılım başvurunuz alındı. Size de bir onay e-postası gönderdik.', 'program');
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
            'program_id' => $program->id,
            'kvkk_accepted' => true,
            'status' => ApplicationStatus::Pending,
        ]);

        $this->processEventRegistration->handle($registration);

        return FormStatus::redirect('Katılım başvurunuz alındı. Size de bir onay e-postası gönderdik.', 'program');
    }
}
