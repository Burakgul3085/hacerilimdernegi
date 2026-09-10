<?php

namespace Tests\Feature;

use App\Enums\MediaType;
use App\Enums\ProgramType;
use App\Models\Event;
use App\Models\MediaAlbum;
use App\Models\MediaItem;
use App\Models\Page;
use App\Models\Post;
use App\Models\Program;
use App\Support\SiteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FrontendContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_public_page_renders(): void
    {
        $program = $this->makeProgram();
        $event = $this->makeEvent();
        $post = $this->makePost();
        $album = $this->makeAlbum();

        Page::query()->create([
            'slug' => 'hakkimizda',
            'title' => 'Hakkımızda',
            'excerpt' => 'Dernek hakkında',
            'body' => '<p>Kurum metni</p>',
            'is_published' => true,
        ]);

        $urls = [
            '/',
            '/hakkimizda',
            '/programlar',
            route('programs.show', $program),
            '/etkinlikler',
            route('events.show', $event),
            '/yazilar',
            route('posts.show', $post),
            '/medya',
            route('media.show', $album),
            '/canli',
            '/uyelik',
            '/bagis',
            '/iletisim',
            '/ara?q=sohbet',
        ];

        foreach ($urls as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_old_legal_urls_redirect_home_because_modals_replaced_pages(): void
    {
        $this->get('/yasal/kvkk')->assertRedirect('/');
        $this->get('/yasal/gizlilik')->assertRedirect('/');
        $this->get('/yasal/cerezler')->assertRedirect('/');
    }

    public function test_home_page_renders_hero_and_pillars_from_settings(): void
    {
        SiteSettings::put('hero_title', 'Panelden gelen başlık');
        SiteSettings::put('hero_text', 'Panelden gelen açıklama');
        SiteSettings::put('cta_title', 'Panelden gelen çağrı');
        SiteSettings::put('value_pillars', json_encode([
            ['icon' => 'book', 'title' => 'Panel değeri', 'text' => 'Panel açıklaması'],
        ], JSON_UNESCAPED_UNICODE));

        $this->get('/')
            ->assertOk()
            ->assertSee('Panelden gelen başlık')
            ->assertSee('Panelden gelen açıklama')
            ->assertSee('Panelden gelen çağrı')
            ->assertSee('Panel değeri');
    }

    public function test_navigation_menu_comes_from_settings(): void
    {
        SiteSettings::put('nav_items', json_encode([
            ['label' => 'Özel bağlantı', 'url' => '/ozel-sayfa'],
        ], JSON_UNESCAPED_UNICODE));

        $this->get('/')
            ->assertOk()
            ->assertSee('Özel bağlantı')
            ->assertSee('/ozel-sayfa')
            ->assertDontSee('>Programlar<', false);
    }

    public function test_footer_credits_the_developer_from_settings(): void
    {
        SiteSettings::put('developer_label', 'Tasarım ve yazılım');
        SiteSettings::put('developer_name', 'Burak Gül');
        SiteSettings::put('developer_url', 'https://www.linkedin.com/in/burakgul1006/');
        SiteSettings::put('developer_email', 'burakgul3085@gmail.com');

        $this->get('/')
            ->assertOk()
            ->assertSee('Tasarım ve yazılım')
            ->assertSee('Burak Gül')
            ->assertSee('https://www.linkedin.com/in/burakgul1006/', false)
            ->assertSee('mailto:burakgul3085@gmail.com', false);
    }

    public function test_footer_credit_disappears_when_the_developer_name_is_cleared(): void
    {
        SiteSettings::put('developer_name', '');

        $this->get('/')
            ->assertOk()
            ->assertDontSee('Tasarım ve yazılım')
            ->assertDontSee('mailto:burakgul3085@gmail.com', false);
    }

    public function test_legal_modal_content_comes_from_site_settings(): void
    {
        SiteSettings::put('kvkk_text', 'Panel KVKK metni özel ifadeyle');
        SiteSettings::put('privacy_text', 'Panel gizlilik metni özel ifadeyle');
        SiteSettings::put('cookie_text', 'Panel çerez metni özel ifadeyle');

        $this->get('/')
            ->assertOk()
            ->assertSee('Panel KVKK metni özel ifadeyle', false)
            ->assertSee('Panel gizlilik metni özel ifadeyle', false)
            ->assertSee('Panel çerez metni özel ifadeyle', false)
            ->assertSee('openLegal', false);
    }

    public function test_program_list_filters_by_type_and_search_term(): void
    {
        $this->makeProgram([
            'title' => 'Tefsir dersi',
            'slug' => 'tefsir-dersi',
            'type' => ProgramType::Ders,
            'description' => '<p>Ders açıklaması</p>',
        ]);
        $this->makeProgram([
            'title' => 'Haftalık sohbet',
            'slug' => 'haftalik-sohbet',
            'type' => ProgramType::Sohbet,
        ]);

        $this->get('/programlar?tur='.ProgramType::Ders->value)
            ->assertOk()
            ->assertSee('Tefsir dersi')
            ->assertDontSee('Haftalık sohbet');

        $this->get('/programlar?ara=sohbet')
            ->assertOk()
            ->assertSee('Haftalık sohbet')
            ->assertDontSee('Tefsir dersi');
    }

    public function test_event_page_offers_a_calendar_link(): void
    {
        $event = $this->makeEvent(['title' => 'Dönem açılışı', 'slug' => 'donem-acilisi']);

        $this->get(route('events.show', $event))
            ->assertOk()
            ->assertSee('Takvime ekle')
            ->assertSee('calendar.google.com', false);
    }

    public function test_event_list_filters_by_month(): void
    {
        $thisMonth = $this->makeEvent([
            'title' => 'Bu ayki etkinlik',
            'slug' => 'bu-ayki-etkinlik',
            'starts_at' => now()->addDays(2),
        ]);
        $this->makeEvent([
            'title' => 'Gelecek aydaki etkinlik',
            'slug' => 'gelecek-aydaki-etkinlik',
            'starts_at' => now()->addMonths(2),
        ]);

        $this->get('/etkinlikler?ay='.$thisMonth->starts_at->format('Y-m'))
            ->assertOk()
            ->assertSee('Bu ayki etkinlik')
            ->assertDontSee('Gelecek aydaki etkinlik');
    }

    public function test_search_finds_published_records_across_sections(): void
    {
        $this->makeProgram(['title' => 'Kitap tahlili buluşması', 'slug' => 'kitap-tahlili-bulusmasi']);
        $this->makePost(['title' => 'Kitap okuma listesi', 'slug' => 'kitap-okuma-listesi']);

        $this->get('/ara?q=kitap')
            ->assertOk()
            ->assertSee('Kitap tahlili buluşması')
            ->assertSee('Kitap okuma listesi');
    }

    public function test_search_reports_when_nothing_matches(): void
    {
        $this->get('/ara?q=bulunmayanbirkelime')
            ->assertOk()
            ->assertSee('Sonuç bulunamadı');
    }

    public function test_unpublished_records_stay_hidden(): void
    {
        $program = $this->makeProgram(['is_published' => false]);

        $this->get('/programlar')->assertOk()->assertDontSee($program->title);
        $this->get(route('programs.show', $program))->assertNotFound();
        $this->get('/ara?q='.urlencode($program->title))->assertOk()->assertSee('Sonuç bulunamadı');
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeProgram(array $attributes = []): Program
    {
        return Program::query()->create([
            'type' => ProgramType::Sohbet,
            'title' => 'Haftalık sohbet',
            'slug' => 'haftalik-sohbet',
            'instructor' => 'Dernek hocaları',
            'description' => '<p>Sohbet açıklaması</p>',
            'starts_at' => now()->addDays(3),
            'location' => 'Şehitkamil / Gaziantep',
            'is_published' => true,
            ...$attributes,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeEvent(array $attributes = []): Event
    {
        return Event::query()->create([
            'title' => 'Dönem açılış programı',
            'slug' => 'donem-acilis-programi',
            'description' => '<p>Etkinlik açıklaması</p>',
            'starts_at' => now()->addDays(10),
            'location' => 'Şehitkamil / Gaziantep',
            'registration_open' => true,
            'is_published' => true,
            ...$attributes,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makePost(array $attributes = []): Post
    {
        return Post::query()->create([
            'type' => 'announcement',
            'title' => 'Web sitemiz yayında',
            'slug' => 'web-sitemiz-yayinda',
            'excerpt' => 'Kısa özet',
            'body' => '<p>Yazı gövdesi</p>',
            'published_at' => now()->subDay(),
            'is_published' => true,
            ...$attributes,
        ]);
    }

    private function makeAlbum(): MediaAlbum
    {
        $album = MediaAlbum::query()->create([
            'title' => 'Açılış programı',
            'slug' => 'acilis-programi',
            'description' => 'Albüm açıklaması',
            'is_published' => true,
        ]);

        MediaItem::query()->create([
            'media_album_id' => $album->id,
            'type' => MediaType::Video,
            'title' => 'Program kaydı',
            'external_url' => 'https://youtube.com/watch?v=abcdef',
            'sort_order' => 1,
        ]);

        return $album;
    }
}
