<?php

namespace App\Support;

use App\Models\Event;
use App\Models\Program;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ProgramFeed
{
    /**
     * @return Collection<int, WorkItem>
     */
    public static function upcoming(int $limit = 4): Collection
    {
        return self::collect('yaklasan', '')->take($limit)->values();
    }

    /**
     * @return Collection<string, Collection<int, WorkItem>>
     */
    public static function grouped(string $scope, string $month): Collection
    {
        return self::collect($scope, $month)->groupBy(
            fn (WorkItem $item): string => $item->groupLabel(),
        );
    }

    /**
     * @return array<string, string>
     */
    public static function monthOptions(string $scope): array
    {
        $threshold = now()->subDay();

        $programQuery = Program::query()->published()->whereNotNull('starts_at');
        $eventQuery = Event::query()->published()->whereNotNull('starts_at');

        if ($scope === 'gecmis') {
            $programQuery->where('starts_at', '<', $threshold)->orderByDesc('starts_at');
            $eventQuery->where('starts_at', '<', $threshold)->orderByDesc('starts_at');
        } else {
            $programQuery->where('starts_at', '>=', now()->startOfMonth())->orderBy('starts_at');
            $eventQuery->where('starts_at', '>=', now()->startOfMonth())->orderBy('starts_at');
        }

        return $programQuery->get(['starts_at'])
            ->concat($eventQuery->get(['starts_at']))
            ->mapWithKeys(fn ($record) => [$record->starts_at->format('Y-m') => $record->starts_at->translatedFormat('F Y')])
            ->all();
    }

    /**
     * @return Collection<int, WorkItem>
     */
    private static function collect(string $scope, string $month): Collection
    {
        $programs = Program::query()->published();
        $events = Event::query()->published();

        self::constrainScope($programs, $scope);
        self::constrainScope($events, $scope);
        self::constrainMonth($programs, $month);
        self::constrainMonth($events, $month);

        if ($scope === 'gecmis') {
            $programs->orderByDesc('starts_at');
            $events->orderByDesc('starts_at');
        } else {
            $programs->orderBy('starts_at');
            $events->orderBy('starts_at');
        }

        $items = $programs->get()->map(fn (Program $program) => WorkItem::fromProgram($program))
            ->concat($events->get()->map(fn (Event $event) => WorkItem::fromEvent($event)));

        return $scope === 'gecmis'
            ? $items->sortByDesc(fn (WorkItem $item) => $item->startsAt?->timestamp ?? 0)->values()
            : $items->sortBy(fn (WorkItem $item) => $item->startsAt?->timestamp ?? PHP_INT_MAX)->values();
    }

    private static function constrainScope(Builder $query, string $scope): void
    {
        if ($scope === 'gecmis') {
            $query->whereNotNull('starts_at')->where('starts_at', '<', now()->subDay());

            return;
        }

        $query->where(fn (Builder $builder) => $builder
            ->whereNull('starts_at')
            ->orWhere('starts_at', '>=', now()->subDay()));
    }

    private static function constrainMonth(Builder $query, string $month): void
    {
        if (preg_match('/^\d{4}-\d{2}$/', $month) !== 1) {
            return;
        }

        [$year, $monthNumber] = explode('-', $month);

        $query->whereYear('starts_at', $year)->whereMonth('starts_at', $monthNumber);
    }
}
