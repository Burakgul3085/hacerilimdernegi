<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\MediaAlbum;
use App\Models\Post;
use App\Models\Program;
use App\Support\SiteSettings;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        return view('pages.home', [
            'programs' => Program::query()->published()->upcoming()->limit(3)->get(),
            'events' => Event::query()->published()->where(function ($query): void {
                $query->whereNull('starts_at')->orWhere('starts_at', '>=', now()->subDay());
            })->orderBy('starts_at')->limit(4)->get(),
            'posts' => Post::query()->with('category')->published()->latest('published_at')->latest()->limit(3)->get(),
            'albums' => MediaAlbum::query()->published()->withCount('items')->latest()->limit(3)->get(),
            'settings' => SiteSettings::all(),
        ]);
    }
}
