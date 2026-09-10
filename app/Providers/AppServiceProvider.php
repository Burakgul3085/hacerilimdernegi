<?php

namespace App\Providers;

use App\Support\SiteSettings;
use Carbon\Carbon;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Carbon::setLocale('tr');

        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }

        Paginator::defaultView('pagination.hacer');
        Paginator::defaultSimpleView('pagination.hacer');

        RateLimiter::for('forms', function (Request $request) {
            $email = Str::lower($request->string('email')->toString());

            return [
                Limit::perMinute(5)->by($request->ip()),
                Limit::perMinute(8)->by(($email !== '' ? $email : 'anon').'|'.$request->ip()),
            ];
        });

        RateLimiter::for('search', function (Request $request) {
            return Limit::perMinute(30)->by($request->ip());
        });

        View::composer(['layouts.app', 'layouts.error', 'pages.*', 'errors.*', 'errors::*'], function ($view): void {
            $view->with('settings', SiteSettings::all());
            $view->with('logoUrl', SiteSettings::logoUrl());
        });
    }
}
