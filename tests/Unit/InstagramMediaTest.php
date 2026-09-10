<?php

namespace Tests\Unit;

use App\Support\InstagramMedia;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class InstagramMediaTest extends TestCase
{
    public function test_normalizes_post_and_reel_links_and_strips_query_tokens(): void
    {
        $post = InstagramMedia::tryFrom('https://www.instagram.com/p/AbC123xyz/?igsh=hello');
        $reel = InstagramMedia::tryFrom('https://instagram.com/reel/ReelsCode99/?stkn=secret');

        $this->assertNotNull($post);
        $this->assertSame('https://www.instagram.com/p/AbC123xyz/', $post->url);
        $this->assertSame('https://www.instagram.com/p/AbC123xyz/embed/captioned/', $post->embedUrl());
        $this->assertSame('Gönderi', $post->label());

        $this->assertNotNull($reel);
        $this->assertSame('https://www.instagram.com/reel/ReelsCode99/', $reel->url);
        $this->assertSame('Reels', $reel->label());
    }

    public function test_cleans_a_profile_url_and_drops_session_tokens(): void
    {
        $this->assertSame(
            'https://www.instagram.com/hacerilimkultur/',
            InstagramMedia::profileUrl('https://www.instagram.com/hacerilimkultur?stkn=M3lxMmtnNjc3NmF3'),
        );
    }

    #[DataProvider('rejectedUrls')]
    public function test_rejects_urls_that_are_not_instagram_posts(string $url): void
    {
        $this->assertNull(InstagramMedia::tryFrom($url));
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function rejectedUrls(): array
    {
        return [
            'profile' => ['https://www.instagram.com/hacerilimkultur'],
            'other host' => ['https://evil.example/p/AbC123xyz'],
            'javascript' => ['javascript:alert(1)'],
            'empty' => [''],
        ];
    }

    public function test_collection_keeps_only_valid_instagram_media(): void
    {
        $media = InstagramMedia::collection([
            ['url' => 'https://www.instagram.com/p/GoodPost/'],
            ['url' => 'https://example.com/not-instagram'],
            ['url' => 'javascript:alert(1)'],
        ]);

        $this->assertCount(1, $media);
        $this->assertSame('GoodPost', $media[0]->shortcode);
    }
}
