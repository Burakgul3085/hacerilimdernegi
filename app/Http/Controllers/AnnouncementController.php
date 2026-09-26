<?php

namespace App\Http\Controllers;

use App\Actions\SubmitContentComment;
use App\Models\Announcement;
use App\Support\ShareLinks;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    public function index(): View
    {
        $announcements = Announcement::query()
            ->published()
            ->latest('published_at')
            ->latest()
            ->paginate(9);

        return view('pages.announcements.index', [
            'announcements' => $announcements,
        ]);
    }

    public function show(Announcement $announcement): View
    {
        abort_unless($announcement->isVisibleOnSite(), 404);

        $related = Announcement::query()
            ->published()
            ->whereKeyNot($announcement->getKey())
            ->latest('published_at')
            ->latest()
            ->limit(3)
            ->get();

        $announcement->load('approvedComments');

        return view('pages.announcements.show', [
            'announcement' => $announcement,
            'related' => $related,
            'previous' => $this->neighbouringAnnouncement($announcement, previous: true),
            'next' => $this->neighbouringAnnouncement($announcement, previous: false),
            ...ShareLinks::for($announcement->title, route('announcements.show', $announcement, absolute: true)),
            'comments' => $announcement->approvedComments,
        ]);
    }

    public function storeComment(Request $request, Announcement $announcement, SubmitContentComment $submit): RedirectResponse
    {
        return $submit->handle($request, $announcement);
    }

    private function neighbouringAnnouncement(Announcement $announcement, bool $previous): ?Announcement
    {
        $stamp = $announcement->published_at ?? $announcement->created_at;

        return Announcement::query()
            ->published()
            ->whereKeyNot($announcement->getKey())
            ->where(function (Builder $query) use ($stamp, $announcement, $previous): void {
                $query->where('published_at', $previous ? '<' : '>', $stamp)
                    ->orWhere(function (Builder $inner) use ($stamp, $announcement, $previous): void {
                        $inner->where('published_at', $stamp)
                            ->where('id', $previous ? '<' : '>', $announcement->id);
                    });
            })
            ->when(
                $previous,
                fn (Builder $query) => $query->latest('published_at')->latest('id'),
                fn (Builder $query) => $query->oldest('published_at')->oldest('id'),
            )
            ->first();
    }
}
