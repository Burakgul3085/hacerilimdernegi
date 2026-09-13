<?php

namespace App\Http\Controllers;

use App\Enums\ActivityStatus;
use App\Models\Activity;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityController extends Controller
{
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
}
