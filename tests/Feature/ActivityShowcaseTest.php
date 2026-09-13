<?php

namespace Tests\Feature;

use App\Enums\ProgramType;
use App\Models\Activity;
use App\Models\Event;
use App\Models\Program;
use App\Support\SiteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityShowcaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_activity_index_lists_published_lines_and_hides_drafts(): void
    {
        $published = Activity::factory()->create([
            'title' => 'Yayındaki gençlik hattı',
            'slug' => 'yayindaki-genclik-hatti',
        ]);
        $draft = Activity::factory()->unpublished()->create([
            'title' => 'Taslak faaliyet hattı',
            'slug' => 'taslak-faaliyet-hatti',
        ]);

        $this->get('/faaliyetler')
            ->assertOk()
            ->assertSee('Faaliyetler')
            ->assertSee('Devam ediyor')
            ->assertSee($published->title)
            ->assertSee($published->excerpt)
            ->assertDontSee($draft->title);
    }

    public function test_status_filter_limits_the_showcase(): void
    {
        Activity::factory()->create([
            'title' => 'Süregelen hadis halkası',
            'slug' => 'suregelen-hadis-halkasi',
        ]);
        Activity::factory()->completed()->create([
            'title' => 'Bitmiş yaz kampı',
            'slug' => 'bitmis-yaz-kampi',
        ]);

        $this->get('/faaliyetler?durum=devam')
            ->assertOk()
            ->assertSee('Süregelen hadis halkası')
            ->assertDontSee('Bitmiş yaz kampı');

        $this->get('/faaliyetler?durum=tamamlandi')
            ->assertOk()
            ->assertSee('Bitmiş yaz kampı')
            ->assertDontSee('Süregelen hadis halkası');
    }

    public function test_activity_detail_shows_copy_and_linked_sessions(): void
    {
        $activity = Activity::factory()->create([
            'title' => 'Kitap tahlil hattı',
            'slug' => 'kitap-tahlil-hatti',
            'excerpt' => 'Seçilen eserler üzerine düzenli tahlil.',
            'description' => '<p>Tahlil açıklaması</p>',
        ]);
        Program::query()->create([
            'activity_id' => $activity->id,
            'type' => ProgramType::KitapTahlili,
            'title' => 'Bu ayki tahlil',
            'slug' => 'bu-ayki-tahlil',
            'starts_at' => now()->addDays(4),
            'is_published' => true,
        ]);

        $this->get(route('activities.show', $activity))
            ->assertOk()
            ->assertSee('Kitap tahlil hattı')
            ->assertSee('Seçilen eserler üzerine düzenli tahlil.')
            ->assertSee('Tahlil açıklaması')
            ->assertSee('Yaklaşan oturumlar')
            ->assertSee('Bu ayki tahlil')
            ->assertSee('Destek olun')
            ->assertSee(route('donate', absolute: false), false)
            ->assertDontSee('IBAN');
    }

    public function test_unpublished_activity_is_not_found(): void
    {
        $activity = Activity::factory()->unpublished()->create([
            'title' => 'Gizli faaliyet',
            'slug' => 'gizli-faaliyet',
        ]);

        $this->get(route('activities.show', $activity))->assertNotFound();
        $this->get('/ara?q='.urlencode($activity->title))->assertOk()->assertSee('Sonuç bulunamadı');
    }

    public function test_legacy_calendar_urls_redirect_to_the_activity_showcase(): void
    {
        $this->get('/programlar')->assertRedirect(route('activities.index'));
        $this->get('/etkinlikler')->assertRedirect(route('activities.index'));
        $this->get('/etkinlikler?ay=2026-09')->assertRedirect(route('activities.index'));
    }

    public function test_home_shows_activity_cards_instead_of_a_dated_calendar(): void
    {
        Activity::factory()->create([
            'title' => 'Ana sayfa faaliyet kartı',
            'slug' => 'ana-sayfa-faaliyet-karti',
            'sort_order' => 1,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Ana sayfa faaliyet kartı')
            ->assertSee('Tüm faaliyetler')
            ->assertSee(route('activities.index', absolute: false), false)
            ->assertDontSee('Yaklaşan programlar');
    }

    public function test_search_finds_published_activities(): void
    {
        Activity::factory()->create([
            'title' => 'Kur’an yüzünden okuma hattı',
            'slug' => 'kuran-yuzunden-okuma-hatti',
            'excerpt' => 'Tecvidxyz meal çalışması.',
        ]);

        $this->get('/ara?q=tecvidxyz')
            ->assertOk()
            ->assertSee('Kur’an yüzünden okuma hattı');
    }

    public function test_stored_program_nav_is_renamed_to_activities(): void
    {
        SiteSettings::put('nav_items', json_encode([
            ['label' => 'Hakkımızda', 'url' => '/hakkimizda'],
            ['label' => 'Programlar', 'url' => '/programlar'],
            ['label' => 'Yazılar', 'url' => '/yazilar'],
        ], JSON_UNESCAPED_UNICODE));

        $this->get('/')
            ->assertOk()
            ->assertSee('Faaliyetler')
            ->assertSee('/faaliyetler', false)
            ->assertDontSee('>Programlar<', false);
    }

    public function test_event_detail_still_accepts_registration(): void
    {
        $event = Event::query()->create([
            'title' => 'Dönem açılış programı',
            'slug' => 'donem-acilis-programi',
            'is_published' => true,
            'registration_open' => true,
            'starts_at' => now()->addDays(10),
        ]);

        $this->get(route('events.show', $event))
            ->assertOk()
            ->assertSee('Faaliyetler')
            ->assertSee('Başvuruyu gönder');
    }
}
