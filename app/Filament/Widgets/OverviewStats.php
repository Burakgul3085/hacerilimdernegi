<?php

namespace App\Filament\Widgets;

use App\Models\ContactMessage;
use App\Models\Event;
use App\Models\MembershipApplication;
use App\Models\Program;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OverviewStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Programlar', Program::query()->count()),
            Stat::make('Etkinlikler', Event::query()->count()),
            Stat::make('Üyelik başvuruları', MembershipApplication::query()->where('status', 'pending')->count()),
            Stat::make('Okunmamış mesaj', ContactMessage::query()->where('is_read', false)->count()),
        ];
    }
}
