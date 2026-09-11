<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Support\CorporatePages;
use App\Support\SiteSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PageController extends Controller
{
    public function show(string $slug): View|RedirectResponse
    {
        $prettyRoute = CorporatePages::routeName($slug);

        if ($prettyRoute !== null && request()->routeIs('pages.show')) {
            return redirect()->route($prettyRoute, status: 301);
        }

        $page = Page::query()->published()->where('slug', $slug)->first();

        if (! $page) {
            abort_unless(CorporatePages::has($slug), 404);

            $definition = CorporatePages::definitions()[$slug];
            $excerpt = $slug === 'hakkimizda'
                ? (string) SiteSettings::get('about_excerpt')
                : $definition['excerpt'];

            $page = new Page([
                'title' => $definition['title'],
                'slug' => $slug,
                'excerpt' => $excerpt,
                'body' => $slug === 'hakkimizda'
                    ? '<p>'.e($excerpt).'</p>'
                    : $definition['body'],
            ]);
        }

        return view('pages.static', compact('page'));
    }
}
