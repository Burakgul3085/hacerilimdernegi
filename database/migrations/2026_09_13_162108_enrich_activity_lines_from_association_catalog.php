<?php

use App\Enums\ActivityStatus;
use App\Models\Activity;
use App\Models\ActivitySession;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;

return new class extends Migration
{
    public function up(): void
    {
        foreach ($this->lines() as $line) {
            $sessions = $line['sessions'];
            unset($line['sessions']);

            $activity = Activity::query()->updateOrCreate(
                ['slug' => $line['slug']],
                $line,
            );

            $this->syncSessions($activity, $sessions);
        }

        $this->relabelSaturdayCenterSessions();
    }

    public function down(): void
    {
        Activity::query()->where('slug', 'satir-arasi-seferleri')->delete();
    }

    /**
     * @return list<array{
     *     title: string,
     *     slug: string,
     *     excerpt: string,
     *     cadence: string,
     *     description: string,
     *     highlights: list<array{title: string, text: string}>,
     *     status: ActivityStatus,
     *     sort_order: int,
     *     is_published: bool,
     *     sessions: list<array{starts_at: Carbon, location: string, note: string}>
     * }>
     */
    private function lines(): array
    {
        return [
            [
                'title' => 'Cumartesi merkez dersleri',
                'slug' => 'cumartesi-merkez-dersleri',
                'excerpt' => 'Haftanın merkez halkası. Tefsir ve siyer aynı çatı altında yürür; dönemlik sure çalışmaları burada duyurulur.',
                'cadence' => 'Her cumartesi 13.00 ve 14.45',
                'description' => '<p>Cumartesi, derneğin düzenli ilim halkasının toplandığı gündür. Tefsir dersleri ve siyer okuması aynı çatı altında yürür.</p><p>Tefsir hattında Lokman, Hucurât, Hûd ve Zümer sureleri dönem dönem ele alınır. Sureyi ezberlemek isteyenler için belletmen hocalı, online gruplar ayrıca açılabilir.</p><p>Siyer halkasında Ali Muhammed Sallâbi’nin <em>Siyer-i Nebî</em> adlı eseri birlikte okunur ve tahlil edilir. Saat ve dönem başlığı, bağlı oturumlarda yer alır.</p>',
                'highlights' => [
                    ['title' => 'Bu dönem', 'text' => 'Hûd suresi tefsiri ve Siyer-i Nebî okuması.'],
                    ['title' => 'Nasıl katılır', 'text' => 'Dernek merkezine belirtilen saatte gelmeniz yeterlidir.'],
                ],
                'status' => ActivityStatus::Ongoing,
                'sort_order' => 10,
                'is_published' => true,
                'sessions' => [],
            ],
            [
                'title' => 'Satır arası seferleri',
                'slug' => 'satir-arasi-seferleri',
                'excerpt' => 'Farklı kalemlerin kısa metinleri üzerinde zihin açıcı, çok sesli okuma ve kritik.',
                'cadence' => 'Dönemlik satır arası oturumları',
                'description' => '<p>Satır arası seferleri, kısa sürede daha fazla yol kat etmek için birbirinden farklı isimlerin çarpıcı yazıları üzerinde yapılan çok sesli bir okuma hattıdır.</p><p>Metinler yerinde temin edilir; okumak için süre verilir. Kritik, hazır bir sunum değil, birlikte düşünülen bir sohbettir.</p>',
                'highlights' => [
                    ['title' => 'Metin', 'text' => 'Okuyacağınız yazıları geldiğinizde Hâcer’den alabilirsiniz.'],
                    ['title' => 'Biçim', 'text' => 'Kısa metin, ortak okuma ve zihin açıcı kritik.'],
                ],
                'status' => ActivityStatus::Ongoing,
                'sort_order' => 15,
                'is_published' => true,
                'sessions' => [],
            ],
            [
                'title' => 'Gençlik çalışmaları',
                'slug' => 'genclik-calismalari',
                'excerpt' => '14–18 yaşındaki kızlar için düzenli halka: kıssa, sohbet, atölye ve kardeşlik.',
                'cadence' => 'Cumartesi 14.30–16.30',
                'description' => '<p>Hâcer gençlik çalışmaları, 14–18 yaşındaki kızlarla kurulan düzenli bir muhittir. Niyet, geleceğe umutla bakılacak genç fidanları tanımak ve güzel bir bağ kurmaktır.</p><p>Dönemler kıssa okuması, karakter ve sınır sohbetleri, çözümleme oturumları ve atölyelerle yürür. “Sana zaten âşina”, “Hayatıma kıssa bir bakış” ve “Kökler ve kanatlar” bu hattın dönem başlıklarındandır.</p><p>Katılım dönem formuyla açılır; kontenjan sınırlıdır. Görüntü ve ses kaydı alınmaz.</p>',
                'highlights' => [
                    ['title' => 'Kimler için', 'text' => '14–18 yaşındaki kızlar.'],
                    ['title' => 'Nasıl katılır', 'text' => 'Dönem formu açılınca duyurulur; kontenjan sınırlıdır.'],
                ],
                'status' => ActivityStatus::Ongoing,
                'sort_order' => 20,
                'is_published' => true,
                'sessions' => [
                    $this->upcoming(Carbon::SATURDAY, 14, 30, 'Hâcer genç oturumu'),
                    $this->upcoming(Carbon::SATURDAY, 14, 30, 'Hâcer genç oturumu', 1),
                ],
            ],
            [
                'title' => 'İlim kampları',
                'slug' => 'ilim-kamplari',
                'excerpt' => 'Yoğun ders ve kardeşlik için düzenlenen kamp hatları: hizmet içi eğitim ve Hâcer ruhu üzerine programlar.',
                'cadence' => 'Dönemlik kamp çağrıları',
                'description' => '<p>Kamplar, derslerin daha yoğun yürüdüğü, kardeşliğin de birlikte kurulduğu dönemlerdir.</p><p><em>Yeni Bir Başlangıç</em>, Fatih Sultan Semiz ile hanımlara yönelik aylık bir eğitim hattıdır. Pazar günleri 09.30–15.30 arasında yürür; her oturumda davet, niyet ve hizmet içi bir başlık ele alınır.</p><p><em>Allah Bizi Zâyi Etmez</em>, Hâcer annesini anlamak ve o duruşu bugüne taşımak üzere kurulan yatılı kamp hattıdır. Kontenjan sınırlıdır; çocuk kabul edilmez. Yeni çağrılar bu sayfada duyurulur.</p>',
                'highlights' => [
                    ['title' => 'Yeni Bir Başlangıç', 'text' => 'Hanımlara yönelik; ayda bir pazar 09.30–15.30.'],
                    ['title' => 'Allah Bizi Zâyi Etmez', 'text' => 'Hâcer ruhunu inşa etmek üzere kurulan kamp hattı.'],
                ],
                'status' => ActivityStatus::Ongoing,
                'sort_order' => 30,
                'is_published' => true,
                'sessions' => [
                    [
                        'starts_at' => Carbon::parse('2025-10-12 09:30'),
                        'location' => 'Dernek merkezi',
                        'note' => 'Yeni Bir Başlangıç — Neye davet, niye davet?',
                    ],
                    [
                        'starts_at' => Carbon::parse('2026-01-16 17:00'),
                        'location' => 'Bağ evi',
                        'note' => 'Allah Bizi Zâyi Etmez kampı',
                    ],
                ],
            ],
            [
                'title' => 'Seminerler',
                'slug' => 'seminerler',
                'excerpt' => 'Konuk hocalar ve özel başlıklarla açılan seminer hattı.',
                'cadence' => 'Duyuru üzerine',
                'description' => '<p>Seminerler, belirli bir meseleyi daha derinlikli ele alan, çoğu zaman konuk hocalarla yürüyen programlardır.</p><p>Fıkıhta ruhsat ve taviz dengesi, bu çağda kulluk, evde İslam’ı yaşamak ve Z kuşağına maneviyatı anlatmak bu hattın örnek başlıklarındandır. Tarih, yer ve katılım koşulu her seminerin kendi oturumunda duyurulur.</p><p>Seminerler hanımlara yöneliktir. Çocuklu katılım, duyuruda aksi yazılmadıkça kabul edilmez.</p>',
                'highlights' => [
                    ['title' => 'Kimler için', 'text' => 'Hanımlara yönelik seminerler.'],
                    ['title' => 'Katılım', 'text' => 'Tarih ve yer her seminerin oturumunda belirtilir.'],
                ],
                'status' => ActivityStatus::Ongoing,
                'sort_order' => 40,
                'is_published' => true,
                'sessions' => [],
            ],
            [
                'title' => 'Riyâzü’s-Sâlihîn',
                'slug' => 'hadis-dersleri',
                'excerpt' => 'Ömrü hadis-i şeriflerle şekillendirmek üzere iki ayrı günde yürüyen okuma halkası.',
                'cadence' => 'Pazartesi 19.00 ve cuma 16.00',
                'description' => '<p>Riyâzü’s-Sâlihîn hadis okumalarının gayesi, ömrü hadis-i şeriflerle şekillendirmektir. Efendimiz sallallahu aleyhi ve sellemin hayatımıza müdahale etmesine izin vermek; dünya hayatında berekete, ahiret hayatında şefaate ermek muradındayız.</p><p>Dersler iki ayrı gün ve saatte, farklı hocalar tarafından yürür. Aynı niyeti taşıyan hanımlar beklenir.</p>',
                'highlights' => [
                    ['title' => 'Gayemiz', 'text' => 'Sünnetin hayata müdahalesine izin vermek.'],
                    ['title' => 'Kimler için', 'text' => 'Aynı niyeti taşıyan hanımlar.'],
                ],
                'status' => ActivityStatus::Ongoing,
                'sort_order' => 50,
                'is_published' => true,
                'sessions' => [
                    $this->upcoming(Carbon::MONDAY, 19, 0, 'Pazartesi halkası'),
                    $this->upcoming(Carbon::FRIDAY, 16, 0, 'Cuma halkası'),
                ],
            ],
            [
                'title' => 'Kur’an-ı Kerim dersleri',
                'slug' => 'kuran-i-kerim',
                'excerpt' => 'Lafzı ve manası şifa olan kitabı indirildiği gibi okuma niyetiyle yüzüne ve tecvid.',
                'cadence' => 'Her cuma 14.30–16.30',
                'description' => '<p>Hem lafzı hem manası şifa olan Kur’ân’ı, indirildiği gibi okuma niyetiyle yola çıktık. Dersler yüz yüze yürür; yüzüne okuma ve tecvid tâlimi yapılır.</p><p>Çalışma hanımlara yöneliktir. Kontenjan sınırlıdır. Ramazan dönemlerinde mukabele ayrıca duyurulur.</p>',
                'highlights' => [
                    ['title' => 'Biçim', 'text' => 'Yüz yüze; yüzüne okuma ve tecvid tâlimi.'],
                    ['title' => 'Kimler için', 'text' => 'Hanımlara yöneliktir. Kontenjan sınırlıdır.'],
                ],
                'status' => ActivityStatus::Ongoing,
                'sort_order' => 60,
                'is_published' => true,
                'sessions' => [
                    $this->upcoming(Carbon::FRIDAY, 14, 30, 'Yüzüne ve tecvid'),
                    $this->upcoming(Carbon::FRIDAY, 14, 30, 'Yüzüne ve tecvid', 1),
                ],
            ],
            [
                'title' => 'Kitap tahlilleri',
                'slug' => 'kitap-tahlilleri',
                'excerpt' => 'Seçilen eserlerin birlikte okunup konuşulduğu tahlil halkası.',
                'cadence' => 'Dönemlik tahlil oturumları',
                'description' => '<p>Kitap tahlilleri, seçilen bir eserin birlikte okunduğu ve üzerine konuşulduğu düzenli buluşmalardır.</p><p>Dönem örnekleri arasında <em>Öfke Dansı</em>, <em>Allah’a Hüsnüzan Beslemek</em> ve <em>Geminin Neresindeyiz?</em> yer alır. Dönemin kitabı ve oturum tarihi, bağlı tahlil kaydında duyurulur.</p>',
                'highlights' => [
                    ['title' => 'Dönem örnekleri', 'text' => 'Öfke Dansı; Allah’a Hüsnüzan Beslemek; Geminin Neresindeyiz?'],
                    ['title' => 'Katılım', 'text' => 'Dönemin kitabı ve tarihi oturum kaydında yer alır.'],
                ],
                'status' => ActivityStatus::Ongoing,
                'sort_order' => 70,
                'is_published' => true,
                'sessions' => [],
            ],
            [
                'title' => 'İlmihal çalışmaları',
                'slug' => 'ilmihal-dersleri',
                'excerpt' => 'Bir Müslümanın bilmesi gereken temel hükümleri pekiştiren düzenli ders hattı.',
                'cadence' => 'Her cumartesi 11.00–12.30',
                'description' => '<p>İlmihal çalışmaları, bir Müslümanın mutlaka bilmesi gereken temel konuları pekiştirerek öğretmeyi hedefler. Başlangıç seviyesi dersleri on beş haftalık bir dönem halinde yürür; ödev, etüt ve karşılıklı mütalaa ile desteklenir.</p><p>Hocalar eşliğinde alt grup çalışması yapıldığı için devam, katılımın şartıdır. Eğitici eğitimi, hanımlara özel haller ve ilmihal evi projesi bu hattın diğer kollarıdır.</p><p>Dersler yüz yüze, hanımlara yöneliktir.</p>',
                'highlights' => [
                    ['title' => 'Dönem', 'text' => 'On beş hafta; cumartesi 11.00–12.30, yüz yüze.'],
                    ['title' => 'Şart', 'text' => 'Ödev ve alt grup çalışması olduğu için devam zorunludur.'],
                ],
                'status' => ActivityStatus::Ongoing,
                'sort_order' => 80,
                'is_published' => true,
                'sessions' => [
                    $this->upcoming(Carbon::SATURDAY, 11, 0, 'Başlangıç seviyesi ilmihal'),
                    $this->upcoming(Carbon::SATURDAY, 11, 0, 'Başlangıç seviyesi ilmihal', 1),
                ],
            ],
            [
                'title' => 'Psikoloji çalışmaları',
                'slug' => 'psikoloji-sohbetleri',
                'excerpt' => 'İmanını heyecanla yaşamak isteyen psikolog hanımların tecrübe paylaştığı çalışma hattı.',
                'cadence' => 'Cuma 16.30–17.30',
                'description' => '<p>Bu hat, imanını heyecanla yaşamak isteyen psikolog hanımların tecrübelerini paylaşması ve birlikte gelişebilmesi için kuruldu. Kitaplar ekseninde psikosohbetler ve dosya oturumları aynı çatı altında yürür.</p><p>Buluşmalar ikindi namazının birlikte kılınmasıyla başlar. Katılım, psikoloji ve PDR öğrencisi veya mezunu hanımlarla sınırlıdır; kontenjan vardır.</p>',
                'highlights' => [
                    ['title' => 'Kimler için', 'text' => 'Psikoloji ve PDR öğrencisi veya mezunu hanımlar.'],
                    ['title' => 'Akış', 'text' => 'İkindi namazıyla başlar; kontenjan sınırlıdır.'],
                ],
                'status' => ActivityStatus::Ongoing,
                'sort_order' => 90,
                'is_published' => true,
                'sessions' => [
                    $this->upcoming(Carbon::FRIDAY, 16, 30, 'Dosya oturumu'),
                    $this->upcoming(Carbon::FRIDAY, 16, 30, 'Dosya oturumu', 1),
                ],
            ],
            [
                'title' => 'Kadın, evlilik ve aile',
                'slug' => 'kadin-evlilik-aile',
                'excerpt' => 'Evlilik, kadınlık ve bekarlık üzerine yürüyen ders ve çember sohbetleri.',
                'cadence' => 'İki haftada bir çarşamba 17.00',
                'description' => '<p>Bu hat, kadın, evlilik ve aile meselelerini ders ve çember sohbeti olarak ele alır.</p><p><em>İşretü’n-Nisâ ekseninde evliliğin saklı kodları</em>, İmâm Nesâî’nin eseri üzerinden Resûlullah sallallahu aleyhi ve sellemin aile hayatını ve mahremiyet ölçülerini okur. On oturumluk dönem evlilere özeldir; düzenli okuma ve ödev takibi esastır.</p><p><em>Kadınlık rollerinde görünmeyen yükler</em> ve <em>Bekarlık üzerine</em> seminerleri, tavsiye değil birlikte sorgulama çemberi olarak açılır. Kontenjan sınırlıdır.</p>',
                'highlights' => [
                    ['title' => 'İşretü’n-Nisâ', 'text' => 'Evlilere özel; on oturum, devam zorunlu.'],
                    ['title' => 'Bekarlık üzerine', 'text' => 'Bekarlığı bir bekleyiş değil oluş süreci olarak ele alan seminer.'],
                ],
                'status' => ActivityStatus::Ongoing,
                'sort_order' => 100,
                'is_published' => true,
                'sessions' => [
                    $this->upcoming(Carbon::WEDNESDAY, 17, 0, 'İşretü’n-Nisâ okuması'),
                    $this->upcoming(Carbon::WEDNESDAY, 17, 0, 'İşretü’n-Nisâ okuması', 2),
                ],
            ],
        ];
    }

    /**
     * @param  list<array{starts_at: Carbon, location: string, note: string}>  $sessions
     */
    private function syncSessions(Activity $activity, array $sessions): void
    {
        if ($sessions === [] || $activity->sessions()->exists()) {
            return;
        }

        foreach ($sessions as $session) {
            ActivitySession::query()->create([
                'activity_id' => $activity->id,
                ...$session,
            ]);
        }
    }

    private function relabelSaturdayCenterSessions(): void
    {
        $center = Activity::query()->where('slug', 'cumartesi-merkez-dersleri')->first();

        if ($center === null) {
            return;
        }

        $center->sessions()
            ->where('note', 'Tefsir ve merkez sohbeti')
            ->update(['note' => 'Hûd suresi tefsiri ve Siyer-i Nebî']);
    }

    /**
     * @return array{starts_at: Carbon, location: string, note: string}
     */
    private function upcoming(int $weekday, int $hour, int $minute, string $note, int $weeks = 0): array
    {
        $startsAt = now()->next($weekday)->setTime($hour, $minute);

        if ($startsAt->isSameDay(now()) && $startsAt->lt(now())) {
            $startsAt->addWeek();
        }

        return [
            'starts_at' => $startsAt->addWeeks($weeks),
            'location' => 'Dernek merkezi',
            'note' => $note,
        ];
    }
};
