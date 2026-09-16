<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\MediaAlbum;
use App\Models\Post;
use App\Support\SiteSettings;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $homeActivities = Activity::query()->published()->with('sessions')->ordered()->limit(3)->get();

        return view('pages.home', [
            'homeActivities' => $homeActivities,
            'featuredActivity' => $homeActivities->first(),
            'posts' => Post::query()->with('category')->published()->latest('published_at')->latest()->limit(3)->get(),
            'albums' => MediaAlbum::query()
                ->published()
                ->roots()
                ->withCount(['items', 'children' => fn ($query) => $query->published()])
                ->latest()
                ->limit(3)
                ->get(),
            'settings' => SiteSettings::all(),
        ]);
    }
}
