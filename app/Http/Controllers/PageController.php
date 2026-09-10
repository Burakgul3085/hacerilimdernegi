<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Support\SiteSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PageController extends Controller
{
    public function show(string $slug): View|RedirectResponse
    {
        if ($slug === 'hakkimizda' && request()->routeIs('pages.show')) {
            return redirect()->route('about', status: 301);
        }

        $page = Page::query()->published()->where('slug', $slug)->first();

        if (! $page) {
            abort_unless($slug === 'hakkimizda', 404);

            $page = new Page([
                'title' => 'Hakkımızda',
                'slug' => 'hakkimizda',
                'excerpt' => SiteSettings::get('about_excerpt'),
                'body' => '<p>'.e(SiteSettings::get('about_excerpt')).'</p>',
            ]);
        }

        return view('pages.static', compact('page'));
    }
}
