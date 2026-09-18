<?php

namespace App\Enums;

enum CalendarReminderStatus: string
{
    case None = 'none';
    case Pending = 'pending';
    case Sent = 'sent';
    case Failed = 'failed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::None => 'Yok',
            self::Pending => 'Bekliyor',
            self::Sent => 'Gönderildi',
            self::Failed => 'Hata',
            self::Cancelled => 'İptal',
        };
    }
}
