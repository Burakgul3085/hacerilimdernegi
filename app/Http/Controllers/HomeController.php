<?php

namespace App\Http\Controllers;

use App\Models\MediaAlbum;
use App\Models\Post;
use App\Support\ProgramFeed;
use App\Support\SiteSettings;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $upcoming = ProgramFeed::upcoming(4);

        return view('pages.home', [
            'upcoming' => $upcoming,
            'nextHighlight' => $upcoming->first(),
            'posts' => Post::query()->with('category')->published()->latest('published_at')->latest()->limit(3)->get(),
            'albums' => MediaAlbum::query()->published()->withCount('items')->latest()->limit(3)->get(),
            'settings' => SiteSettings::all(),
        ]);
    }
}
