<?php

namespace App\Console\Commands;

use App\Actions\SendDueAdminCalendarReminders;
use Illuminate\Console\Command;

class SendAdminCalendarRemindersCommand extends Command
{
    protected $signature = 'calendar:send-reminders {--limit=50 : En fazla kaç hatırlatma işlensin}';

    protected $description = 'Vadesi gelen kişisel takvim hatırlatmalarını e-posta ile gönderir';

    public function handle(SendDueAdminCalendarReminders $action): int
    {
        $result = $action->handle((int) $this->option('limit'));

        $this->info("Gönderilen: {$result['sent']} · Başarısız: {$result['failed']}");

        return self::SUCCESS;
    }
}
