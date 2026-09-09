<?php

namespace App\Http\Controllers;

use App\Enums\ProgramType;
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
            'posts' => Post::query()->published()->latest('published_at')->latest()->limit(3)->get(),
            'events' => Event::query()->published()->orderBy('starts_at')->limit(3)->get(),
            'albums' => MediaAlbum::query()->published()->latest()->limit(3)->get(),
            'programTypes' => ProgramType::cases(),
            'settings' => SiteSettings::all(),
        ]);
    }
}
