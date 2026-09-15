<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use Illuminate\Contracts\Database\Eloquent\Builder;
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

        $shareUrl = route('announcements.show', $announcement, absolute: true);
        $shareText = $announcement->title.' — '.$shareUrl;

        return view('pages.announcements.show', [
            'announcement' => $announcement,
            'related' => $related,
            'previous' => $this->neighbouringAnnouncement($announcement, previous: true),
            'next' => $this->neighbouringAnnouncement($announcement, previous: false),
            'shareUrl' => $shareUrl,
            'whatsappShareUrl' => 'https://wa.me/?text='.rawurlencode($shareText),
        ]);
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
