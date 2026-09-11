<?php

namespace Tests\Feature;

use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CorporatePageTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('prettyPages')]
    public function test_corporate_page_renders_at_its_pretty_url(string $url, string $title): void
    {
        $this->get($url)
            ->assertOk()
            ->assertSee($title);
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function prettyPages(): array
    {
        return [
            'vision' => ['/vizyon-misyon', 'Vizyon ve misyon'],
            'message' => ['/baskanin-mesaji', 'Başkanın mesajı'],
            'board' => ['/yonetim-kadrosu', 'Yönetim kadrosu'],
            'bylaws' => ['/dernek-tuzugu', 'Dernek tüzüğü'],
        ];
    }

    public function test_legacy_page_url_redirects_to_the_pretty_corporate_url(): void
    {
        $this->get('/sayfa/vizyon-misyon')
            ->assertStatus(301)
            ->assertRedirect(route('corporate.vision'));
    }

    public function test_unpublished_corporate_page_falls_back_to_placeholder_copy(): void
    {
        Page::query()->where('slug', 'vizyon-misyon')->update([
            'is_published' => false,
            'body' => '<p>Gizli taslak</p>',
        ]);

        $this->get('/vizyon-misyon')
            ->assertOk()
            ->assertSee('Vizyon ve misyon')
            ->assertSee('Derneğin yönü, gayesi ve çalışma ilkeleri.')
            ->assertDontSee('Gizli taslak');
    }

    public function test_bylaws_page_does_not_show_the_decorative_image(): void
    {
        $this->get('/dernek-tuzugu')
            ->assertSee('Dernek tüzüğü')
            ->assertDontSee('bylaws-pdf', false)
            ->assertDontSee('aspect-[4/5]', false);
    }

    public function test_bylaws_page_embeds_the_uploaded_pdf(): void
    {
        Page::query()->where('slug', 'dernek-tuzugu')->update([
            'document' => 'pages/documents/tuzuk.pdf',
        ]);

        $this->get('/dernek-tuzugu')
            ->assertSee('Dernek tüzüğü')
            ->assertSee('bylaws-pdf', false)
            ->assertSee('/storage/pages/documents/tuzuk.pdf', false)
            ->assertSee("PDF'yi indir", false)
            ->assertDontSee('yönetim panelinden eklenecektir')
            ->assertDontSee('aspect-[4/5]', false);
    }
}
