<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class BackupDatabaseCommand extends Command
{
    protected $signature = 'backup:database';

    protected $description = 'SQLite/MySQL dump ve storage yedeği alır';

    public function handle(): int
    {
        $dir = storage_path('app/backups');
        File::ensureDirectoryExists($dir);
        $stamp = now()->format('Y-m-d_His');

        $connection = config('database.default');

        if ($connection === 'sqlite') {
            $source = database_path('database.sqlite');
            if (is_file($source)) {
                File::copy($source, $dir.'/db_'.$stamp.'.sqlite');
            }
        } else {
            $this->warn('MySQL dump için deploy/backup.sh kullanın.');
        }

        $this->info('Yedek klasörü: '.$dir);

        return self::SUCCESS;
    }
}
