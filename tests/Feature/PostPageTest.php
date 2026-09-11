<?php

namespace Tests\Feature;

use App\Filament\Resources\Posts\Schemas\PostForm;
use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_unpublished_post_returns_404(): void
    {
        $post = $this->makePost([
            'is_published' => false,
            'slug' => 'taslak-yazi',
        ]);

        $this->get(route('posts.show', $post))->assertNotFound();
    }

    public function test_future_post_returns_404(): void
    {
        $this->freezeTime();

        $post = $this->makePost([
            'slug' => 'ileri-tarihli',
            'published_at' => now()->addDay(),
        ]);

        $this->get(route('posts.show', $post))->assertNotFound();
    }

    public function test_article_page_renders_cover_quote_source_and_share_controls(): void
    {
        $author = User::factory()->create(['name' => 'Ayşe Hoca']);
        $category = Category::query()->create([
            'name' => 'Duyurular',
            'slug' => 'duyurular',
            'type' => 'announcement',
        ]);

        $post = $this->makePost([
            'author_id' => $author->id,
            'category_id' => $category->id,
            'title' => 'Web sitemiz yayında',
            'slug' => 'web-sitemiz-yayinda',
            'subtitle' => 'Dernek gündemini buradan izleyin',
            'excerpt' => 'Kısa özet metni',
            'location' => 'Şehitkamil / Gaziantep',
            'image' => 'posts/cover.jpg',
            'gallery' => ['posts/gallery/one.jpg'],
            'featured_quote' => 'İlim, sohbet ve kültür.',
            'source_label' => 'Resmî duyuru',
            'source_url' => 'https://example.com/duyuru',
            'body' => '<p>'.str_repeat('kelime ', 200).'</p>',
        ]);

        $this->get(route('posts.show', $post))
            ->assertOk()
            ->assertSee('Web sitemiz yayında')
            ->assertSee('Dernek gündemini buradan izleyin')
            ->assertSee('Kısa özet metni')
            ->assertSee('Şehitkamil / Gaziantep')
            ->assertSee('Ayşe Hoca')
            ->assertSee('Duyurular')
            ->assertSee('İlim, sohbet ve kültür.')
            ->assertSee('Resmî duyuru')
            ->assertSee('https://example.com/duyuru', false)
            ->assertSee('/storage/posts/cover.jpg', false)
            ->assertSee('/storage/posts/gallery/one.jpg', false)
            ->assertSee('2 dk okuma')
            ->assertSee('Bağlantıyı kopyala')
            ->assertSee('https://wa.me/?text=', false)
            ->assertSee('data-url="'.route('posts.show', $post, absolute: true).'"', false)
            ->assertSee('og:type" content="article', false)
            ->assertSee('property="og:image" content="'.$post->coverAbsoluteUrl().'"', false)
            ->assertSee('post-cover-img', false)
            ->assertSee('post-gallery-item', false)
            ->assertDontSee('object-cover', false)
            ->assertDontSee('aspect-[16/9]', false);
    }

    public function test_related_posts_prefer_the_same_type(): void
    {
        $current = $this->makePost([
            'type' => 'article',
            'title' => 'Asıl yazı',
            'slug' => 'asil-yazi',
            'published_at' => now()->subDays(2),
        ]);
        $this->makePost([
            'type' => 'article',
            'title' => 'Kardeş yazı',
            'slug' => 'kardes-yazi',
            'published_at' => now()->subDay(),
        ]);
        $this->makePost([
            'type' => 'announcement',
            'title' => 'Başka duyuru',
            'slug' => 'baska-duyuru',
            'published_at' => now(),
        ]);

        $this->get(route('posts.show', $current))
            ->assertOk()
            ->assertSeeInOrder(['Kardeş yazı', 'Başka duyuru']);
    }

    public function test_previous_and_next_links_follow_publish_order(): void
    {
        $older = $this->makePost([
            'title' => 'Eski duyuru',
            'slug' => 'eski-duyuru',
            'published_at' => now()->subDays(3),
        ]);
        $current = $this->makePost([
            'title' => 'Güncel duyuru',
            'slug' => 'guncel-duyuru',
            'published_at' => now()->subDay(),
        ]);
        $newer = $this->makePost([
            'title' => 'Yeni duyuru',
            'slug' => 'yeni-duyuru',
            'published_at' => now(),
        ]);

        $this->get(route('posts.show', $current))
            ->assertOk()
            ->assertSee('Eski duyuru')
            ->assertSee('Yeni duyuru')
            ->assertSee(route('posts.show', $older, absolute: false), false)
            ->assertSee(route('posts.show', $newer, absolute: false), false);
    }

    public function test_escapes_article_fields_that_come_from_the_panel(): void
    {
        $post = $this->makePost([
            'title' => 'Güvenli başlık',
            'slug' => 'guvenli-baslik',
            'type' => '<script>alert("type")</script>',
            'author_name' => '<img src=x onerror=alert(2)>',
            'subtitle' => '<script>alert("sub")</script>',
            'excerpt' => '<img src=x onerror=alert(1)>',
            'location' => '<b>Salon</b>',
            'featured_quote' => '<script>alert("quote")</script>',
            'source_label' => '<script>alert("src")</script>',
            'source_url' => 'https://example.com/kaynak',
        ]);

        $this->get(route('posts.show', $post))
            ->assertOk()
            ->assertDontSee('<script>alert("sub")</script>', false)
            ->assertDontSee('<script>alert("type")</script>', false)
            ->assertDontSee('<img src=x onerror=alert(1)>', false)
            ->assertDontSee('<img src=x onerror=alert(2)>', false)
            ->assertDontSee('<b>Salon</b>', false)
            ->assertDontSee('<script>alert("quote")</script>', false)
            ->assertDontSee('<script>alert("src")</script>', false)
            ->assertSee('&lt;script&gt;alert(&quot;sub&quot;)&lt;/script&gt;', false);
    }

    public function test_rejects_a_javascript_source_url(): void
    {
        $post = $this->makePost([
            'slug' => 'kotu-kaynak',
            'source_label' => 'Zararlı kaynak',
            'source_url' => 'javascript:alert(1)',
        ]);

        $this->get(route('posts.show', $post))
            ->assertOk()
            ->assertDontSee('href="javascript:', false)
            ->assertDontSee('Zararlı kaynak');
    }

    public function test_list_cards_show_the_category_name(): void
    {
        $category = Category::query()->create([
            'name' => 'Duyurular',
            'slug' => 'duyurular-listesi',
            'type' => 'announcement',
        ]);
        $this->makePost([
            'category_id' => $category->id,
            'image' => 'posts/cover.jpg',
        ]);

        $this->get(route('posts.index'))
            ->assertOk()
            ->assertSee('Duyurular')
            ->assertSee('post-media-img', false)
            ->assertDontSee('aspect-[16/9]', false);
    }

    public function test_typed_admin_fields_create_a_category_and_render_on_the_page(): void
    {
        $data = PostForm::persistableData([
            'type' => 'Duyuru',
            'category_name' => 'Yeni Kategori',
            'author_name' => 'Misafir Hoca',
            'title' => 'Özel duyuru',
            'slug' => 'ozel-duyuru',
            'excerpt' => 'Özet',
            'body' => '<p>Gövde</p>',
            'is_published' => true,
            'published_at' => now()->subHour(),
        ]);

        $post = Post::query()->create($data);

        $this->assertSame('announcement', $post->type);
        $this->assertSame('Misafir Hoca', $post->author_name);
        $this->assertDatabaseHas('categories', ['name' => 'Yeni Kategori']);
        $this->assertArrayNotHasKey('category_name', $data);

        $this->get(route('posts.show', $post))
            ->assertOk()
            ->assertSee('Duyuru')
            ->assertSee('Yeni Kategori')
            ->assertSee('Misafir Hoca');

        $this->get(route('posts.index', ['tur' => 'announcement']))
            ->assertOk()
            ->assertSee('Özel duyuru');
    }

    public function test_typed_category_reuses_an_existing_record(): void
    {
        $category = Category::query()->create([
            'name' => 'Duyurular',
            'slug' => 'duyurular',
            'type' => 'post',
        ]);

        $first = PostForm::persistableData([
            'type' => 'Yazı',
            'category_name' => 'Duyurular',
        ]);
        $second = PostForm::persistableData([
            'type' => 'Yazı',
            'category_name' => 'Duyurular',
        ]);
        $empty = PostForm::persistableData([
            'type' => 'Yazı',
            'category_name' => '  ',
        ]);

        $this->assertSame($category->id, $first['category_id']);
        $this->assertSame($category->id, $second['category_id']);
        $this->assertNull($empty['category_id']);
        $this->assertDatabaseCount('categories', 1);
    }

    public function test_custom_type_renders_the_typed_label(): void
    {
        $post = $this->makePost([
            'type' => 'Basın bülteni',
            'author_name' => 'Misafir Hoca',
            'slug' => 'basin-bulteni',
        ]);

        $this->get(route('posts.show', $post))
            ->assertOk()
            ->assertSee('Basın bülteni')
            ->assertSee('Misafir Hoca')
            ->assertSee('Yazı bilgileri');
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
}
