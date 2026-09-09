<?php

namespace App\Http\Controllers;

use App\Models\MediaAlbum;
use Illuminate\View\View;

class MediaController extends Controller
{
    public function index(): View
    {
        return view('pages.media.index', [
            'albums' => MediaAlbum::query()->published()->latest()->paginate(9),
        ]);
    }

    public function show(MediaAlbum $album): View
    {
        abort_unless($album->is_published, 404);

        $album->load('items');

        return view('pages.media.show', compact('album'));
    }
}
