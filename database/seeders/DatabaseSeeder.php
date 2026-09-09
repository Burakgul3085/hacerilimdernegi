<?php

namespace Database\Seeders;

use App\Enums\ProgramType;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Event;
use App\Models\Page;
use App\Models\Post;
use App\Models\Program;
use App\Models\Setting;
use App\Models\User;
use App\Support\SiteSettings;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'info@hacerilimvekulturdernegi.org'],
            [
                'name' => 'Süper yönetici',
                'password' => Hash::make(env('ADMIN_PASSWORD', 'password')),
                'role' => UserRole::SuperAdmin,
            ],
        );

        foreach (SiteSettings::defaults() as $key => $value) {
            Setting::query()->firstOrCreate(
                ['key' => $key],
                ['value' => $value],
            );
        }

        SiteSettings::put('kvkk_text', "Hâcer İlim ve Kültür Derneği olarak iletişim, üyelik, etkinlik kaydı ve e-bülten formları aracılığıyla ad, e-posta, telefon ve mesaj verilerinizi 6698 sayılı KVKK kapsamında işleriz.\n\nVeriler, Hostinger KVM sunucusunda (Almanya, Frankfurt) saklanır. Amaç: başvuruları değerlendirmek, duyuru göndermek ve dernek iletişimini yürütmektir.\n\nHaklarınız: bilgi alma, düzeltme, silme ve itiraz. Talepleriniz için iletişim formunu kullanabilirsiniz.");
        SiteSettings::put('privacy_text', 'Gizlilik politikası: Toplanan kişisel veriler yalnızca dernek faaliyetleri için kullanılır, üçüncü kişilerle pazarlama amacıyla paylaşılmaz. Sunucu Almanya’dadır.');
        SiteSettings::put('cookie_text', 'Sitede oturum, güvenlik ve tercih çerezleri kullanılır. İstatistik için üçüncü taraf çerez eklenirse bu metin güncellenir.');

        Page::query()->updateOrCreate(
            ['slug' => 'hakkimizda'],
            [
                'title' => 'Hakkımızda',
                'excerpt' => 'Gaziantep Şehitkamil’de faaliyet gösteren Hâcer İlim ve Kültür Derneği; Kur’an ve sünnet ışığında ilim, kültür ve kardeşlik çalışmalarını sürdürür.',
                'body' => '<p>Hâcer İlim ve Kültür Derneği, 2017’den bu yana Gaziantep’te ilim ve kültür faaliyetleri yürüten bağımsız bir topluluktur.</p><p>Gayemiz; Kur’an-ı Kerim’i ve hadis-i şerifleri daha iyi anlayıp hayatımıza geçirmek, ilim ve kardeşlik etrafında faydalı çalışmalar yapmaktır.</p><h2>Faaliyetlerimiz</h2><p>Kur’an-ı Kerim ve hadis dersleri, ilmihâl dersleri, lise gençlik ve çocuk çalışmaları, seminerler, kitap tahlilleri ve kamplar düzenlenir. Programların güncel tarih ve kapsamı etkinlik takviminde duyurulur.</p><p>Adres: Karacaahmet, 38012 Nolu Cadde No: 36A, Bina 111 Kat 1 Daire 1, 27590 Şehitkamil / Gaziantep.</p>',
                'is_published' => true,
            ],
        );

        $category = Category::query()->updateOrCreate(
            ['slug' => 'duyurular'],
            ['name' => 'Duyurular', 'type' => 'announcement'],
        );

        Program::query()->updateOrCreate(
            ['slug' => 'haftalik-sohbet'],
            [
                'type' => ProgramType::Sohbet,
                'title' => 'Haftalık sohbet',
                'instructor' => 'Dernek hocaları',
                'description' => '<p>Haftalık sohbet programımız ilim ve edep üzerine kısa dersler içerir.</p>',
                'starts_at' => now()->addDays(3)->setTime(19, 30),
                'location' => 'Karacaahmet, Şehitkamil / Gaziantep',
                'is_published' => true,
            ],
        );

        Program::query()->updateOrCreate(
            ['slug' => 'kitap-tahlili'],
            [
                'type' => ProgramType::KitapTahlili,
                'title' => 'Kitap tahlili',
                'instructor' => 'Misafir hoca',
                'description' => '<p>Seçilen eserler üzerine tahlil ve müzakere.</p>',
                'starts_at' => now()->addDays(10)->setTime(16, 0),
                'location' => 'Karacaahmet, Şehitkamil / Gaziantep',
                'is_published' => true,
            ],
        );

        Event::query()->updateOrCreate(
            ['slug' => 'acilis-programi'],
            [
                'title' => 'Dönem açılış programı',
                'description' => '<p>Yeni dönem faaliyetlerimizin tanıtılacağı açılış programı.</p>',
                'starts_at' => now()->addDays(20)->setTime(14, 0),
                'location' => 'Karacaahmet, Şehitkamil / Gaziantep',
                'registration_open' => true,
                'is_published' => true,
            ],
        );

        Post::query()->updateOrCreate(
            ['slug' => 'hos-geldiniz'],
            [
                'category_id' => $category->id,
                'type' => 'announcement',
                'title' => 'Web sitemiz yayında',
                'excerpt' => 'Dernek faaliyetlerimizi buradan takip edebilirsiniz.',
                'body' => '<p>Programlar, duyurular ve bağış bilgileri yönetim panelinden güncellenir.</p>',
                'published_at' => now(),
                'is_published' => true,
            ],
        );
    }
}
