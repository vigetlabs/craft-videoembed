<?php

use PHPUnit\Framework\TestCase;
use viget\videoembed\services\VideoEmbed;

/**
 * Service-boundary coverage for the VideoEmbed service (issue #53).
 *
 * PR #42 made getEmbedUrl() a thin wrapper over getVideoData() and isVideoUrl()
 * a delegate to ParsingHelper::getVideoTypeFromUrl(). These tests pin that
 * delegation at the service boundary so a regression in the service layer is
 * caught independently of the ParsingHelper unit tests.
 *
 * The service instantiates without a Craft application because every method
 * delegates to static ParsingHelper calls.
 */
final class VideoEmbedTest extends TestCase
{
    private VideoEmbed $service;

    protected function setUp(): void
    {
        $this->service = new VideoEmbed();
    }

    public function testGetEmbedUrlReturnsYouTubeEmbedUrl(): void
    {
        $this->assertSame(
            'https://www.youtube.com/embed/dQw4w9WgXcQ?rel=0',
            $this->service->getEmbedUrl('https://www.youtube.com/watch?v=dQw4w9WgXcQ')
        );
    }

    public function testGetEmbedUrlReturnsVimeoEmbedUrl(): void
    {
        $this->assertSame(
            'https://player.vimeo.com/video/9999999999',
            $this->service->getEmbedUrl('https://vimeo.com/9999999999')
        );
    }

    public function testGetEmbedUrlReturnsNullForNonVideoUrl(): void
    {
        $this->assertNull($this->service->getEmbedUrl('https://notavideo.com/x'));
    }

    /**
     * getEmbedUrl() is defined as getVideoData()?->embedUrl; pin that identity so
     * the wrapper cannot drift from the canonical data method.
     */
    public function testGetEmbedUrlMirrorsGetVideoDataEmbedUrl(): void
    {
        foreach ([
            'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'https://vimeo.com/9999999999',
        ] as $url) {
            $this->assertSame(
                $this->service->getVideoData($url)?->embedUrl,
                $this->service->getEmbedUrl($url),
                "getEmbedUrl() must equal getVideoData()->embedUrl for {$url}"
            );
        }
    }

    public function testIsVideoUrlAcceptsYouTubeAndVimeo(): void
    {
        $this->assertTrue($this->service->isVideoUrl('https://www.youtube.com/watch?v=dQw4w9WgXcQ'));
        $this->assertTrue($this->service->isVideoUrl('https://vimeo.com/9999999999'));
    }

    /**
     * The service reflects ParsingHelper's exact-host matching (#42): a host that
     * merely contains "youtube.com" as a substring is not a video URL.
     */
    public function testIsVideoUrlRejectsSubstringFakeHost(): void
    {
        $this->assertFalse($this->service->isVideoUrl('https://notyoutube.com/watch?v=ID'));
    }

    /**
     * isVideoUrl() now agrees with getVideoData(): it returns true only when an
     * embeddable video can be produced, so a host-valid URL with no extractable
     * ID is not a video URL (issue #54) — closing the null-deref surface for
     * guard-then-use callers.
     */
    public function testIsVideoUrlAgreesWithGetVideoData(): void
    {
        // Host-valid but no extractable ID: both agree it is not a video.
        $this->assertFalse($this->service->isVideoUrl('https://youtube.com/'));
        $this->assertNull($this->service->getVideoData('https://youtube.com/'));

        // Full URLs still resolve to true, matching a non-null getVideoData().
        $this->assertTrue($this->service->isVideoUrl('https://www.youtube.com/watch?v=dQw4w9WgXcQ'));
        $this->assertNotNull($this->service->getVideoData('https://www.youtube.com/watch?v=dQw4w9WgXcQ'));
        $this->assertTrue($this->service->isVideoUrl('https://vimeo.com/9999999999'));
    }
}
