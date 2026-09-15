<?php

namespace App\Http\Controllers;

use App\Actions\ProcessEventRegistration;
use App\Enums\ActivityStatus;
use App\Enums\ApplicationStatus;
use App\Models\Activity;
use App\Models\EventRegistration;
use App\Support\FormGuard;
use App\Support\FormStatus;
use App\Support\RegistrationForm;
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

    public function registerForm(Activity $activity): View
    {
        abort_unless($activity->acceptsRegistrations(), 404);

        return view('pages.activities.register', [
            'activity' => $activity,
        ]);
    }

    public function register(Request $request, Activity $activity): RedirectResponse
    {
        abort_unless($activity->acceptsRegistrations(), 404);

        if (FormGuard::isBot($request)) {
            return FormStatus::redirect('Katılım başvurunuz alındı. Size de bir onay e-postası gönderdik.', 'activity');
        }

        $fields = $activity->registrationFieldDefinitions();
        $data = $request->validate(RegistrationForm::validationRules($fields));
        $answers = RegistrationForm::collectAnswers($fields, $data);

        $registration = EventRegistration::query()->create([
            'activity_id' => $activity->id,
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'notes' => RegistrationForm::notesSummary($answers),
            'answers' => $answers === [] ? null : $answers,
            'kvkk_accepted' => true,
            'status' => ApplicationStatus::Pending,
        ]);

        $this->processEventRegistration->handle($registration);

        return FormStatus::redirect('Katılım başvurunuz alındı. Size de bir onay e-postası gönderdik.', 'activity');
    }
}
