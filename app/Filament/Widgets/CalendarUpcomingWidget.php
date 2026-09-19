<?php

namespace App\Filament\Widgets;

use App\Enums\CalendarReminderStatus;
use App\Filament\Pages\MyCalendar;
use App\Models\AdminCalendarEntry;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CalendarUpcomingWidget extends StatsOverviewWidget
{
    protected static ?int $sort = -5;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return [];
        }

        $timezone = (string) config('app.timezone');
        $todayStart = now()->timezone($timezone)->startOfDay();
        $todayEnd = now()->timezone($timezone)->endOfDay();
        $weekEnd = $todayEnd->copy()->addDays(7);

        $today = AdminCalendarEntry::query()
            ->visibleTo($user)
            ->whereBetween('starts_at', [$todayStart, $todayEnd])
            ->count();

        $upcoming = AdminCalendarEntry::query()
            ->visibleTo($user)
            ->where('starts_at', '>', $todayEnd)
            ->where('starts_at', '<=', $weekEnd)
            ->count();

        $pending = AdminCalendarEntry::query()
            ->visibleTo($user)
            ->where('reminder_status', CalendarReminderStatus::Pending)
            ->count();

        $next = AdminCalendarEntry::query()
            ->visibleTo($user)
            ->where('starts_at', '>=', now())
            ->orderBy('starts_at')
            ->value('title');

        return [
            Stat::make('Bugünkü notlar', (string) $today)
                ->description($next ? 'Sıradaki: '.$next : 'Takviminiz boş')
                ->descriptionIcon('heroicon-o-calendar-days')
                ->color('warning')
                ->url(MyCalendar::getUrl()),
            Stat::make('7 gün içinde', (string) $upcoming)
                ->description('Yaklaşan kayıtlar')
                ->color('primary')
                ->url(MyCalendar::getUrl()),
            Stat::make('Bekleyen hatırlatma', (string) $pending)
                ->description('E-posta kuyruğu')
                ->color($pending > 0 ? 'warning' : 'success')
                ->url(MyCalendar::getUrl()),
        ];
    }
}
