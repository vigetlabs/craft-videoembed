<?php

use PHPUnit\Framework\TestCase;
use viget\videoembed\enums\VideoType;
use viget\videoembed\helpers\ParsingHelper;

final class ParsingHelperTest extends TestCase
{
    public function testGetVideoTypeFromUrl(): void
    {
        $this->assertEquals(
            VideoType::YOUTUBE,
            ParsingHelper::getVideoTypeFromUrl('https://www.youtube.com')
        );
    }

    public function testGetYouTubeIdFromUrl(): void
    {
        $this->assertEquals(
            '6xWpo5Dn254',
            ParsingHelper::getYouTubeIdFromUrl('https://www.youtube.com/watch?v=6xWpo5Dn254')
        );

        $this->assertEquals(
            '6xWpo5Dn254',
            ParsingHelper::getYouTubeIdFromUrl('https://www.youtube.com/watch?vi=6xWpo5Dn254')
        );

        $this->assertEquals(
            '--HXLM8GuxA',
            ParsingHelper::getYouTubeIdFromUrl('https://youtu.be/--HXLM8GuxA?si=pNahKJLszKr8J00u')
        );

        $this->assertEquals(
            '--HXLM8GuxA',
            ParsingHelper::getYouTubeIdFromUrl('https://youtube.com/watch?v=--HXLM8GuxA')
        );
    }

    public function testGetVimeoIdFromUrl(): void
    {
        $this->assertEquals(
            '9999999999',
            ParsingHelper::getVimeoIdFromUrl('https://vimeo.com/9999999999')
        );

        $this->assertEquals(
            '9999999999',
            ParsingHelper::getVimeoIdFromUrl('https://vimeo.com/9999999999/0000000000')
        );

        $this->assertEquals(
            '9999999999',
            ParsingHelper::getVimeoIdFromUrl('https://player.vimeo.com/video/9999999999')
        );

        $this->assertEquals(
            '9999999999',
            ParsingHelper::getVimeoIdFromUrl('https://player.vimeo.com/video/9999999999?h=0000000000')
        );
    }

    public function testGetVimeoHashFromUrl(): void
    {
        $this->assertNull(
            ParsingHelper::getVimeoHashFromUrl('https://vimeo.com/9999999999')
        );

        $this->assertEquals(
            '0000000000',
            ParsingHelper::getVimeoHashFromUrl('https://vimeo.com/9999999999/0000000000')
        );

        $this->assertNull(
            ParsingHelper::getVimeoHashFromUrl('https://player.vimeo.com/video/9999999999')
        );

        $this->assertEquals(
            '0000000000',
            ParsingHelper::getVimeoHashFromUrl('https://player.vimeo.com/video/9999999999?h=0000000000')
        );
    }

    public function testGetYouTubeCanonicalUrl(): void
    {
        $this->assertEquals(
            'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            ParsingHelper::getVideoDataFromUrl('https://www.youtube.com/watch?v=dQw4w9WgXcQ')->url
        );

        $this->assertEquals(
            'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            ParsingHelper::getVideoDataFromUrl('https://youtu.be/dQw4w9WgXcQ')->url
        );

        $this->assertEquals(
            'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            ParsingHelper::getVideoDataFromUrl('https://youtu.be/dQw4w9WgXcQ?si=abc123')->url
        );

        $this->assertEquals(
            'https://www.youtube.com/watch?v=--HXLM8GuxA',
            ParsingHelper::getVideoDataFromUrl('https://youtube.com/watch?v=--HXLM8GuxA')->url
        );
    }

    public function testGetVimeoEmbedUrl(): void
    {
        $this->assertEquals(
            'https://player.vimeo.com/video/9999999999',
            ParsingHelper::getVideoDataFromUrl('https://vimeo.com/9999999999')->embedUrl
        );

        $this->assertEquals(
            'https://player.vimeo.com/video/9999999999?h=0000000000',
            ParsingHelper::getVideoDataFromUrl('https://vimeo.com/9999999999/0000000000')->embedUrl
        );

        $this->assertEquals(
            'https://player.vimeo.com/video/9999999999',
            ParsingHelper::getVideoDataFromUrl('https://player.vimeo.com/video/9999999999')->embedUrl
        );

        $this->assertEquals(
            'https://player.vimeo.com/video/9999999999?h=0000000000',
            ParsingHelper::getVideoDataFromUrl('https://player.vimeo.com/video/9999999999?h=0000000000')->embedUrl
        );
    }

    // =========================================================================
    // YouTube Shorts parsing
    // =========================================================================

    public function testGetYouTubeIdFromUrlShorts(): void
    {
        $this->assertEquals(
            'dQw4w9WgXcQ',
            ParsingHelper::getYouTubeIdFromUrl('https://www.youtube.com/shorts/dQw4w9WgXcQ')
        );

        $this->assertEquals(
            'dQw4w9WgXcQ',
            ParsingHelper::getYouTubeIdFromUrl('https://youtube.com/shorts/dQw4w9WgXcQ?feature=share')
        );

        // The /shorts/ prefix is matched case-insensitively.
        $this->assertEquals(
            'dQw4w9WgXcQ',
            ParsingHelper::getYouTubeIdFromUrl('https://www.youtube.com/Shorts/dQw4w9WgXcQ')
        );

        // A /shorts/ URL with no ID must return null, not the literal "shorts".
        $this->assertNull(
            ParsingHelper::getYouTubeIdFromUrl('https://www.youtube.com/shorts')
        );
        $this->assertNull(
            ParsingHelper::getYouTubeIdFromUrl('https://www.youtube.com/shorts/')
        );
    }

    // =========================================================================
    // isVertical metadata
    // =========================================================================

    /**
     * A YouTube Shorts URL yields a vertical VideoData while still resolving a
     * correct ID and embed URL — the flag and parsing coexist.
     */
    public function testIsVerticalForShorts(): void
    {
        $video = ParsingHelper::getVideoDataFromUrl('https://www.youtube.com/shorts/dQw4w9WgXcQ');

        $this->assertNotNull($video);
        $this->assertTrue($video->isVertical);
        $this->assertEquals('dQw4w9WgXcQ', $video->id);
        $this->assertEquals('https://www.youtube.com/embed/dQw4w9WgXcQ', $video->embedUrl);
    }

    public function testIsVerticalIsFalseForNonShortsYouTube(): void
    {
        $this->assertFalse(
            ParsingHelper::getVideoDataFromUrl('https://www.youtube.com/watch?v=dQw4w9WgXcQ')->isVertical,
            'watch?v= URL is not vertical'
        );

        $this->assertFalse(
            ParsingHelper::getVideoDataFromUrl('https://youtu.be/dQw4w9WgXcQ')->isVertical,
            'youtu.be URL is not vertical'
        );
    }

    public function testIsVerticalIsFalseForVimeo(): void
    {
        $this->assertFalse(
            ParsingHelper::getVideoDataFromUrl('https://vimeo.com/9999999999')->isVertical
        );
    }

    public function testGetVideoDataReturnsNullForUnknownUrl(): void
    {
        $this->assertNull(
            ParsingHelper::getVideoDataFromUrl('https://example.com/video')
        );
    }
}
