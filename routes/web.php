<?php

use App\Http\Controllers\EventController;
use App\Http\Controllers\FormController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProgramController;
use App\Http\Controllers\SearchController;
use App\Support\SiteSettings;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

View::composer(['layouts.app', 'layouts.error', 'pages.*', 'errors.*', 'errors::*'], function ($view): void {
    $view->with('settings', SiteSettings::all());
    $view->with('logoUrl', SiteSettings::logoUrl());
});

Route::get('/', HomeController::class)->name('home');
Route::get('/hakkimizda', function () {
    return app(PageController::class)->show('hakkimizda');
})->name('about');
Route::get('/programlar', [ProgramController::class, 'index'])->name('programs.index');
Route::get('/programlar/{program:slug}', [ProgramController::class, 'show'])->name('programs.show');
Route::get('/etkinlikler', [EventController::class, 'index'])->name('events.index');
Route::get('/etkinlikler/{event:slug}', [EventController::class, 'show'])->name('events.show');
Route::post('/etkinlikler/{event:slug}/kayit', [EventController::class, 'register'])
    ->middleware('throttle:forms')
    ->name('events.register');
Route::get('/yazilar', [PostController::class, 'index'])->name('posts.index');
Route::get('/yazilar/{post:slug}', [PostController::class, 'show'])->name('posts.show');
Route::get('/medya', [MediaController::class, 'index'])->name('media.index');
Route::get('/medya/{album:slug}', [MediaController::class, 'show'])->name('media.show');
Route::get('/seckiler', [FormController::class, 'social'])->name('social');
Route::permanentRedirect('/sosyal', '/seckiler');
Route::permanentRedirect('/canli', '/seckiler');
Route::get('/uyelik', [FormController::class, 'membership'])->name('membership');
Route::post('/uyelik', [FormController::class, 'storeMembership'])->middleware('throttle:forms')->name('membership.store');
Route::get('/bagis', [FormController::class, 'donate'])->name('donate');
Route::get('/iletisim', [FormController::class, 'contact'])->name('contact');
Route::post('/iletisim', [FormController::class, 'storeContact'])->middleware('throttle:forms')->name('contact.store');
Route::post('/bulten', [FormController::class, 'newsletter'])->middleware('throttle:forms')->name('newsletter.store');
Route::get('/ara', SearchController::class)->name('search');
Route::redirect('/yasal/{type}', '/', 301)->whereIn('type', ['kvkk', 'gizlilik', 'cerezler']);
Route::get('/sayfa/{slug}', [PageController::class, 'show'])->name('pages.show');
