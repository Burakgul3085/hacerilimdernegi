<?php

namespace Database\Seeders;

use App\Enums\ProgramType;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Event;
use App\Models\Page;
use App\Models\Post;
use App\Models\Program;
use App\Models\User;
use App\Support\SiteSettings;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'yonetim@hacerilim.org'],
            [
                'name' => 'Süper yönetici',
                'password' => Hash::make(env('ADMIN_PASSWORD', 'password')),
                'role' => UserRole::SuperAdmin,
            ],
        );

        foreach (SiteSettings::defaults() as $key => $value) {
            SiteSettings::put($key, $value);
        }

        SiteSettings::put('kvkk_text', "Hacer İlim ve Kültür Derneği olarak iletişim, üyelik, etkinlik kaydı ve e-bülten formları aracılığıyla ad, e-posta, telefon ve mesaj verilerinizi 6698 sayılı KVKK kapsamında işleriz.\n\nVeriler, Hostinger KVM sunucusunda (Almanya, Frankfurt) saklanır. Amaç: başvuruları değerlendirmek, duyuru göndermek ve dernek iletişimini yürütmektir.\n\nHaklarınız: bilgi alma, düzeltme, silme ve itiraz. Talepleriniz için iletişim formunu kullanabilirsiniz.");
        SiteSettings::put('privacy_text', 'Gizlilik politikası: Toplanan kişisel veriler yalnızca dernek faaliyetleri için kullanılır, üçüncü kişilerle pazarlama amacıyla paylaşılmaz. Sunucu Almanya’dadır.');
        SiteSettings::put('cookie_text', 'Sitede oturum, güvenlik ve tercih çerezleri kullanılır. İstatistik için üçüncü taraf çerez eklenirse bu metin güncellenir.');

        Page::query()->updateOrCreate(
            ['slug' => 'hakkimizda'],
            [
                'title' => 'Hakkımızda',
                'excerpt' => 'Gaziantep merkezli Hacer İlim ve Kültür Derneği; ders, sohbet ve kitap tahlili etrafında ciddi ve sıcak bir ilim muhiti kurmayı amaçlar.',
                'body' => '<p>Derneğimiz Gaziantep’te ilim, kültür ve kardeşlik zemininde bir araya gelen gönüllülerden oluşur.</p><p>Düzenli dersler, sohbetler ve kitap tahlilleri; kurumsal bir disiplinle, mahremiyet ve edebe riayet edilerek yürütülür.</p><p>Yapılanmamız şeffaf, idaresi ise yönetim kurulunun sorumluluğundadır. Web sitesindeki tüm içerik yönetim panelinden güncellenir.</p>',
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
                'location' => 'Gaziantep',
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
                'location' => 'Gaziantep',
                'is_published' => true,
            ],
        );

        Event::query()->updateOrCreate(
            ['slug' => 'acilis-programi'],
            [
                'title' => 'Dönem açılış programı',
                'description' => '<p>Yeni dönem faaliyetlerimizin tanıtılacağı açılış programı.</p>',
                'starts_at' => now()->addDays(20)->setTime(14, 0),
                'location' => 'Gaziantep',
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
