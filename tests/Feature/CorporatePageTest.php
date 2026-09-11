<?php

namespace Tests\Feature;

use App\Enums\BoardTier;
use App\Enums\UserRole;
use App\Filament\Resources\Pages\Pages\EditPage;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
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
            ->assertDontSee('page-aside-photo', false);
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
            ->assertSee('min-height: 35rem', false)
            ->assertSee("PDF'yi indir", false)
            ->assertDontSee('view=FitH', false)
            ->assertDontSee('yönetim panelinden eklenecektir')
            ->assertDontSee('page-aside-photo', false);
    }

    public function test_board_page_does_not_show_the_decorative_image(): void
    {
        $this->get('/yonetim-kadrosu')
            ->assertSee('Yönetim kadrosu')
            ->assertDontSee('page-aside-photo', false);
    }

    public function test_board_page_renders_members_in_hierarchy(): void
    {
        Page::query()->where('slug', 'yonetim-kadrosu')->update([
            'board_members' => [
                [
                    'name' => 'Ali Üye',
                    'title' => 'Üye',
                    'tier' => 4,
                    'bio' => 'Yönetim kurulu üyesi.',
                    'photo' => null,
                ],
                [
                    'name' => 'Ayşe Yılmaz',
                    'title' => 'Başkan',
                    'tier' => 1,
                    'bio' => 'Dernek başkanı.',
                    'photo' => 'pages/board/ayse.jpg',
                ],
                [
                    'name' => 'Mehmet Demir',
                    'title' => 'Başkan yardımcısı',
                    'tier' => 2,
                    'bio' => null,
                    'photo' => null,
                ],
                [
                    'name' => 'Fatma Kaya',
                    'title' => 'Sayman',
                    'tier' => 3,
                    'bio' => null,
                    'photo' => null,
                ],
                [
                    'name' => '',
                    'title' => 'Görünmemeli',
                    'tier' => 1,
                ],
            ],
        ]);

        $this->get('/yonetim-kadrosu')
            ->assertSee('Yönetim kadrosu')
            ->assertSeeInOrder([
                'Başkanlık',
                'Ayşe Yılmaz',
                'Başkan yardımcıları',
                'Mehmet Demir',
                'Yönetim kurulu',
                'Sayman',
                'Fatma Kaya',
                'Üyeler',
                'Ali Üye',
            ])
            ->assertSee('/storage/pages/board/ayse.jpg', false)
            ->assertSee('board-photo-fit', false)
            ->assertSee('board-photo-fill', false)
            ->assertDontSee('object-cover', false)
            ->assertSee('board-directory', false)
            ->assertDontSee('Görünmemeli')
            ->assertDontSee('yönetim panelinden eklenecektir')
            ->assertDontSee('page-aside-photo', false);
    }

    public function test_board_page_escapes_member_text(): void
    {
        Page::query()->where('slug', 'yonetim-kadrosu')->update([
            'board_members' => [
                [
                    'name' => "<script>alert('xss')</script>",
                    'title' => '<b>Başkan</b>',
                    'tier' => 1,
                    'bio' => '<img src=x onerror=alert(1)>',
                ],
            ],
        ]);

        $this->get('/yonetim-kadrosu')
            ->assertSee('&lt;script&gt;', false)
            ->assertDontSee("<script>alert('xss')</script>", false)
            ->assertDontSee('<b>Başkan</b>', false)
            ->assertDontSee('<img src=x onerror=alert(1)>', false);
    }

    public function test_board_page_accepts_a_photo_stored_as_a_single_item_array(): void
    {
        Page::query()->where('slug', 'yonetim-kadrosu')->update([
            'board_members' => [[
                'name' => 'Ayşe Yılmaz',
                'title' => 'Başkan',
                'tier' => BoardTier::President->value,
                'photo' => ['pages/board/ayse.jpg'],
            ]],
        ]);

        $this->get('/yonetim-kadrosu')
            ->assertSee('/storage/pages/board/ayse.jpg', false);
    }

    public function test_editor_can_save_board_members_from_the_page_form(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Editor]));

        $page = Page::query()->where('slug', 'yonetim-kadrosu')->firstOrFail();

        Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
            ->assertFormFieldIsVisible('board_members')
            ->fillForm([
                'board_members' => [[
                    'name' => 'Ayşe Yılmaz',
                    'title' => 'Başkan',
                    'tier' => BoardTier::President->value,
                    'bio' => 'Dernek başkanı.',
                ]],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Ayşe Yılmaz', $page->refresh()->boardMembers()[0]['name'] ?? null);
        $this->assertSame(BoardTier::President->value, $page->boardMembers()[0]['tier'] ?? null);
    }

    public function test_board_member_fields_are_hidden_on_other_pages(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Editor]));

        $page = Page::query()->where('slug', 'hakkimizda')->firstOrFail();

        Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
            ->assertFormFieldIsHidden('board_members');
    }

    public function test_message_page_shows_the_person_icon_and_default_title_without_a_photo(): void
    {
        $this->get('/baskanin-mesaji')
            ->assertSee('Başkanın mesajı')
            ->assertSee('Hâcer İlim ve Kültür Derneği Başkanı')
            ->assertSee('president-silhouette', false)
            ->assertDontSee('page-aside-photo', false)
            ->assertDontSee('yönetim panelinden eklenecektir');
    }

    public function test_message_page_renders_the_president_portrait_and_message(): void
    {
        Page::query()->where('slug', 'baskanin-mesaji')->update([
            'president_name' => 'Ayşe Yılmaz',
            'president_title' => 'Dernek Başkanı',
            'image' => 'pages/baskan.jpg',
            'body' => '<p>Kıymetli ziyaretçilerimiz, hoş geldiniz.</p>',
        ]);

        $this->get('/baskanin-mesaji')
            ->assertSeeInOrder([
                'Ayşe Yılmaz',
                'Dernek Başkanı',
                'Kıymetli ziyaretçilerimiz, hoş geldiniz.',
            ])
            ->assertSee('/storage/pages/baskan.jpg', false)
            ->assertSee('president-portrait-image', false)
            ->assertSee('object-cover', false)
            ->assertDontSee('board-photo-fill', false)
            ->assertDontSee('board-photo-fit', false)
            ->assertDontSee('president-silhouette', false)
            ->assertDontSee('page-aside-photo', false);
    }

    public function test_message_page_escapes_president_name_and_title(): void
    {
        Page::query()->where('slug', 'baskanin-mesaji')->update([
            'president_name' => "<script>alert('xss')</script>",
            'president_title' => '<b>Başkan</b>',
            'body' => '<p>Güvenli metin</p>',
        ]);

        $this->get('/baskanin-mesaji')
            ->assertSee('&lt;script&gt;', false)
            ->assertDontSee("<script>alert('xss')</script>", false)
            ->assertDontSee('<b>Başkan</b>', false);
    }

    public function test_message_page_keeps_long_unbroken_text_inside_the_message_column(): void
    {
        $run = str_repeat('ahsaak', 40);

        Page::query()->where('slug', 'baskanin-mesaji')->update([
            'president_name' => 'Ayşe Yılmaz',
            'body' => '<p>'.$run.'</p>',
        ]);

        $this->get('/baskanin-mesaji')
            ->assertSee($run)
            ->assertSee('president-message-body', false)
            ->assertSee('overflow-x-clip', false);
    }

    public function test_editor_can_save_president_fields_from_the_page_form(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Editor]));

        $page = Page::query()->where('slug', 'baskanin-mesaji')->firstOrFail();

        Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
            ->assertFormFieldIsVisible('president_name')
            ->assertFormFieldIsVisible('president_title')
            ->fillForm([
                'president_name' => 'Ayşe Yılmaz',
                'president_title' => 'Hâcer İlim ve Kültür Derneği Başkanı',
                'body' => '<p>Hoş geldiniz.</p>',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $page->refresh();

        $this->assertSame('Ayşe Yılmaz', $page->presidentName());
        $this->assertSame('Hâcer İlim ve Kültür Derneği Başkanı', $page->presidentTitle());
    }

    public function test_president_fields_are_hidden_on_other_pages(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Editor]));

        $page = Page::query()->where('slug', 'hakkimizda')->firstOrFail();

        Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
            ->assertFormFieldIsHidden('president_name')
            ->assertFormFieldIsHidden('president_title');
    }
}
