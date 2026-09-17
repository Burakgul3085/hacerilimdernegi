<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('pages')->where('slug', 'baskanin-mesaji')->whereIn('title', [
            'Başkanın Mesajı',
            'Başkanın mesajı',
        ])->update([
            'title' => 'Yöneticinin Mesajı',
            'seo_title' => 'Yöneticinin Mesajı',
        ]);

        DB::table('pages')->where('slug', 'baskanin-mesaji')->whereIn('excerpt', [
            'Dernek başkanının ziyaretçilere mesajı.',
            'Dernek başkanının ziyaretçilere sözü.',
        ])->update([
            'excerpt' => 'Dernek yöneticisinin ziyaretçilere mesajı.',
        ]);
    }

    public function down(): void
    {
        DB::table('pages')->where('slug', 'baskanin-mesaji')->where('title', 'Yöneticinin Mesajı')->update([
            'title' => 'Başkanın Mesajı',
            'seo_title' => 'Başkanın Mesajı',
        ]);

        DB::table('pages')->where('slug', 'baskanin-mesaji')->where('excerpt', 'Dernek yöneticisinin ziyaretçilere mesajı.')->update([
            'excerpt' => 'Dernek başkanının ziyaretçilere mesajı.',
        ]);
    }
};
