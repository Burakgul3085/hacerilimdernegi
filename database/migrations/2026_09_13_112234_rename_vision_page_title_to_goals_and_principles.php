<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('pages')
            ->where('slug', 'vizyon-misyon')
            ->where('title', 'Vizyon ve misyon')
            ->update(['title' => 'Hedef ve ilkelerimiz']);

        DB::table('pages')
            ->where('slug', 'vizyon-misyon')
            ->where('seo_title', 'Vizyon ve misyon')
            ->update(['seo_title' => 'Hedef ve ilkelerimiz']);
    }

    public function down(): void
    {
        DB::table('pages')
            ->where('slug', 'vizyon-misyon')
            ->where('title', 'Hedef ve ilkelerimiz')
            ->update(['title' => 'Vizyon ve misyon']);

        DB::table('pages')
            ->where('slug', 'vizyon-misyon')
            ->where('seo_title', 'Hedef ve ilkelerimiz')
            ->update(['seo_title' => 'Vizyon ve misyon']);
    }
};
