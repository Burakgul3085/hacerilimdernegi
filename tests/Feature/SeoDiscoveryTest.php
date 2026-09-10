<?php

namespace Tests\Feature;

use App\Enums\ProgramType;
use App\Models\Page;
use App\Models\Program;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoDiscoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_robots_txt_allows_the_public_site_and_points_to_the_sitemap(): void
    {
        $this->get('/robots.txt')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertSee('Allow: /', false)
            ->assertSee('Disallow: /yonetim', false)
            ->assertSee('Disallow: /ara', false)
            ->assertSee('Sitemap: ', false)
            ->assertSee('/sitemap.xml', false);
    }

    public function test_sitemap_lists_public_pages_and_omits_admin_search_and_drafts(): void
    {
        $published = Program::query()->create([
            'type' => ProgramType::Sohbet,
            'title' => 'Yayındaki sohbet',
            'slug' => 'yayindaki-sohbet',
            'is_published' => true,
        ]);
        $draft = Program::query()->create([
            'type' => ProgramType::Sohbet,
            'title' => 'Taslak sohbet',
            'slug' => 'taslak-sohbet',
            'is_published' => false,
        ]);

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee(route('home'), false)
            ->assertSee(route('about'), false)
            ->assertSee(route('programs.show', $published), false)
            ->assertDontSee(route('programs.show', $draft), false)
            ->assertDontSee('/yonetim', false)
            ->assertDontSee('/ara', false);
    }

    public function test_search_page_is_excluded_from_indexing(): void
    {
        $this->get('/ara')
            ->assertOk()
            ->assertSee('noindex, follow', false);
    }

    public function test_home_page_declares_canonical_and_index_follow(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('rel="canonical"', false)
            ->assertSee('index, follow', false)
            ->assertSee('og:image', false);
    }

    public function test_legacy_about_url_redirects_to_the_canonical_about_page(): void
    {
        Page::query()->create([
            'slug' => 'hakkimizda',
            'title' => 'Hakkımızda',
            'is_published' => true,
        ]);

        $this->get('/sayfa/hakkimizda')
            ->assertStatus(301)
            ->assertRedirect(route('about'));
    }
}
