<?php

namespace Tests\Unit;

use App\Enums\MediaType;
use App\Support\UploadRules;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class UploadRulesTest extends TestCase
{
    #[DataProvider('mediaPaths')]
    public function test_classifies_an_uploaded_path_by_its_extension(string $path, MediaType $type): void
    {
        $this->assertSame($type, UploadRules::typeFromPath($path));
    }

    /**
     * @return array<string, array{0: string, 1: MediaType}>
     */
    public static function mediaPaths(): array
    {
        return [
            'jpeg photo' => ['pages/gallery/sohbet.jpg', MediaType::Photo],
            'mp4 video' => ['media/kamp.mp4', MediaType::Video],
            'webm video' => ['albums/acilis.webm', MediaType::Video],
            'mp3 audio' => ['media/ders.mp3', MediaType::Audio],
        ];
    }

    public function test_treats_a_blank_path_as_a_photo(): void
    {
        $this->assertSame(MediaType::Photo, UploadRules::typeFromPath(null));
        $this->assertSame(MediaType::Photo, UploadRules::typeFromPath(''));
        $this->assertFalse(UploadRules::isVideoPath('notes.txt'));
    }

    public function test_formats_kilobyte_limits_in_the_admin_helper_text(): void
    {
        $this->assertSame('200 MB', UploadRules::formatKb(204800));
        $this->assertSame('2 GB', UploadRules::formatKb(2097152));
        $this->assertSame('512 KB', UploadRules::formatKb(512));
    }
}
