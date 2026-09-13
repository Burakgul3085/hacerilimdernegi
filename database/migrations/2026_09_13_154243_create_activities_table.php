<?php

use App\Enums\ActivityStatus;
use App\Models\Activity;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('description')->nullable();
            $table->string('image')->nullable();
            $table->json('gallery')->nullable();
            $table->string('status')->default(ActivityStatus::Ongoing->value);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();

            $table->index(['is_published', 'sort_order']);
        });

        foreach ($this->catalog() as $activity) {
            Activity::query()->firstOrCreate(
                ['slug' => $activity['slug']],
                $activity,
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
    }

    /**
     * @return list<array{title: string, slug: string, excerpt: string, description: string, status: ActivityStatus, sort_order: int, is_published: bool}>
     */
    private function catalog(): array
    {
        return [
            $this->line(
                'Cumartesi merkez dersleri',
                'cumartesi-merkez-dersleri',
                'Haftanın merkez halkası. Tefsir, sohbet ve dönem dersleri aynı çatı altında yürür.',
                '<p>Cumartesi, derneğin düzenli ilim halkasının toplandığı gündür. Tefsir dersleri, merkez sohbetleri ve dönem programları bu hat altında duyurulur.</p><p>Takvim ve hoca bilgisi, bağlı oturumlarda yer alır. Katılım, ilgili ders veya sohbet sayfasından bildirilir.</p>',
                10,
            ),
            $this->line(
                'Gençlik çalışmaları',
                'genclik-calismalari',
                'Lise ve gençlik halkası: okuma, sohbet ve kardeşlik muhiti.',
                '<p>Gençlik çalışmaları, lise çağındaki gençlerle kurulan düzenli bir muhittir. Okuma, sohbet ve birlikte vakit geçirme bu hattın esasını oluşturur.</p><p>Dönemlik buluşmalar ve kamp çağrıları, bağlı oturumlar üzerinden paylaşılır.</p>',
                20,
            ),
            $this->line(
                'İlim kampları',
                'ilim-kamplari',
                'Yoğun ders ve kardeşlik için düzenlenen kamp hatları.',
                '<p>Kamplar, derslerin daha yoğun yürüdüğü, kardeşliğin de birlikte kurulduğu dönemlerdir. Yaz ve ara dönem kampları bu hat altında toplanır.</p><p>Kontenjan ve kayıt, ilgili kamp programının sayfasından açılır.</p>',
                30,
            ),
            $this->line(
                'Seminerler',
                'seminerler',
                'Konuk hocalar ve özel başlıklarla açılan seminer hattı.',
                '<p>Seminerler, belirli bir meseleyi daha derinlikli ele alan, çoğu zaman konuk hocalarla yürüyen programlardır.</p><p>Tarih, yer ve katılım koşulu her seminerin kendi kaydında duyurulur.</p>',
                40,
            ),
            $this->line(
                'Hadis dersleri',
                'hadis-dersleri',
                'Hadis-i şerifleri anlamak ve hayata taşımak üzere kurulan ders halkası.',
                '<p>Hadis dersleri, sünneti doğru anlamak ve günlük hayata taşımak için düzenli okunan bir hattır.</p><p>Hoca, metin ve oturum saatleri bağlı ders kayıtlarında yer alır.</p>',
                50,
            ),
            $this->line(
                'Kur’an-ı Kerim',
                'kuran-i-kerim',
                'Okuma, tecvid ve meal çalışmaları bu hat altında yürür.',
                '<p>Kur’an-ı Kerim hattı; yüzünden okuma, tecvid ve meal çalışmalarına ev sahipliği eder.</p><p>Seviye ve grup bilgisi, ilgili ders sayfasında belirtilir.</p>',
                60,
            ),
            $this->line(
                'Kitap tahlilleri',
                'kitap-tahlilleri',
                'Seçilen eserlerin birlikte okunup konuşulduğu tahlil halkası.',
                '<p>Kitap tahlilleri, seçilen bir eserin birlikte okunduğu ve üzerine konuşulduğu düzenli buluşmalardır.</p><p>Dönemin kitabı ve oturum tarihi, bağlı tahlil kaydında duyurulur.</p>',
                70,
            ),
            $this->line(
                'İlmihal dersleri',
                'ilmihal-dersleri',
                'İbadet ve günlük hükümler üzerine sade, düzenli dersler.',
                '<p>İlmihal dersleri, ibadet ve günlük hayata dair hükümleri sade bir dille ele alır.</p><p>Dönem başlıkları ve saatler, bağlı ders kaydında yer alır.</p>',
                80,
            ),
            $this->line(
                'Psikoloji sohbetleri',
                'psikoloji-sohbetleri',
                'İnsan, aile ve iç dünya üzerine ölçülü sohbetler.',
                '<p>Psikoloji sohbetleri, insan ve aile meselelerini ilim ve hikmet çerçevesinde ele alan ölçülü bir hattır.</p><p>Konu ve konuşmacı, her oturumun kendi sayfasında belirtilir.</p>',
                90,
            ),
            $this->line(
                'Kadın, evlilik ve aile',
                'kadin-evlilik-aile',
                'Kadın, evlilik ve aile etrafında yürüyen ders ve sohbetler.',
                '<p>Bu hat, kadın, evlilik ve aile konularını ders ve sohbet formatında ele alır.</p><p>Grup ve katılım bilgisi, bağlı programın sayfasından takip edilir.</p>',
                100,
            ),
        ];
    }

    /**
     * @return array{title: string, slug: string, excerpt: string, description: string, status: ActivityStatus, sort_order: int, is_published: bool}
     */
    private function line(string $title, string $slug, string $excerpt, string $description, int $sortOrder): array
    {
        return [
            'title' => $title,
            'slug' => $slug,
            'excerpt' => $excerpt,
            'description' => $description,
            'status' => ActivityStatus::Ongoing,
            'sort_order' => $sortOrder,
            'is_published' => true,
        ];
    }
};
