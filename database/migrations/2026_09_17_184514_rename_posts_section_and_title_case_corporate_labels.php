<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $pages = [
            'vizyon-misyon' => 'Hedef ve İlkelerimiz',
            'baskanin-mesaji' => 'Başkanın Mesajı',
            'yonetim-kadrosu' => 'Yönetim Kadrosu',
            'dernek-tuzugu' => 'Dernek Tüzüğü',
        ];

        foreach ($pages as $slug => $title) {
            DB::table('pages')->where('slug', $slug)->update([
                'title' => $title,
                'seo_title' => $title,
            ]);
        }

        foreach (['Yazılar ve duyurular', 'Yazılar ve şiirler', 'Yazılar ve Şiirler'] as $old) {
            DB::table('settings')->where('key', 'home_posts_title')->where('value', $old)->update([
                'value' => "Kalemim'İZ",
            ]);
        }
    }

    public function down(): void
    {
        $pages = [
            'vizyon-misyon' => 'Hedef ve ilkelerimiz',
            'baskanin-mesaji' => 'Başkanın mesajı',
            'yonetim-kadrosu' => 'Yönetim kadrosu',
            'dernek-tuzugu' => 'Dernek tüzüğü',
        ];

        foreach ($pages as $slug => $title) {
            DB::table('pages')->where('slug', $slug)->update([
                'title' => $title,
                'seo_title' => $title,
            ]);
        }

        DB::table('settings')->where('key', 'home_posts_title')->where('value', "Kalemim'İZ")->update([
            'value' => 'Yazılar ve şiirler',
        ]);
    }
};
