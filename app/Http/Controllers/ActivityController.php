<?php

namespace App\Http\Controllers;

use App\Actions\ProcessEventRegistration;
use App\Enums\ActivityStatus;
use App\Enums\ApplicationStatus;
use App\Models\Activity;
use App\Models\EventRegistration;
use App\Support\FormGuard;
use App\Support\FormStatus;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityController extends Controller
{
    public function __construct(private ProcessEventRegistration $processEventRegistration) {}

    public function index(Request $request): View
    {
        $scope = match ($request->string('durum')->toString()) {
            'devam' => 'devam',
            'tamamlandi' => 'tamamlandi',
            default => '',
        };

        $activities = Activity::query()
            ->published()
            ->with('sessions')
            ->when($scope === 'devam', fn (Builder $query) => $query->where('status', ActivityStatus::Ongoing))
            ->when($scope === 'tamamlandi', fn (Builder $query) => $query->where('status', ActivityStatus::Completed))
            ->ordered()
            ->get();

        return view('pages.activities.index', [
            'activities' => $activities,
            'currentScope' => $scope,
        ]);
    }

    public function show(Activity $activity): View
    {
        abort_unless($activity->is_published, 404);

        $activity->load('sessions');

        $related = Activity::query()
            ->published()
            ->with('sessions')
            ->whereKeyNot($activity->getKey())
            ->ordered()
            ->limit(3)
            ->get();

        return view('pages.activities.show', [
            'activity' => $activity,
            'nextSession' => $activity->nextSession(),
            'upcomingSessions' => $activity->upcomingSessions(),
            'pastSessions' => $activity->pastSessions(),
            'related' => $related,
        ]);
    }

    public function register(Request $request, Activity $activity): RedirectResponse
    {
        abort_unless($activity->acceptsRegistrations(), 404);

        if (FormGuard::isBot($request)) {
            return FormStatus::redirect('Katılım başvurunuz alındı. Size de bir onay e-postası gönderdik.', 'activity');
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
            'activity_id' => $activity->id,
            'kvkk_accepted' => true,
            'status' => ApplicationStatus::Pending,
        ]);

        $this->processEventRegistration->handle($registration);

        return FormStatus::redirect('Katılım başvurunuz alındı. Size de bir onay e-postası gönderdik.', 'activity');
    }
}
