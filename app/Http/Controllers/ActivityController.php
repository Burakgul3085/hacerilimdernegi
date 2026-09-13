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

        $activity->load([
            'programs' => fn (Builder $query) => $query->published()->upcoming(),
            'events' => fn (Builder $query) => $query->published()
                ->where(fn (Builder $builder) => $builder->whereNull('starts_at')->orWhere('starts_at', '>=', now()->subDay()))
                ->orderBy('starts_at'),
        ]);

        $related = Activity::query()
            ->published()
            ->whereKeyNot($activity->getKey())
            ->ordered()
            ->limit(3)
            ->get();

        return view('pages.activities.show', [
            'activity' => $activity,
            'sessions' => $activity->sessionItems(),
            'related' => $related,
        ]);
    }
}
