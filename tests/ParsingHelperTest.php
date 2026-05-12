<?php

use PHPUnit\Framework\TestCase;
use viget\videoembed\enums\VideoType;
use viget\videoembed\helpers\ParsingHelper;
use viget\videoembed\models\VideoData;

final class ParsingHelperTest extends TestCase
{
    // =========================================================================
    // getVideoTypeFromUrl — scheme validation
    // =========================================================================

    public function testGetVideoTypeFromUrl(): void
    {
        $this->assertEquals(VideoType::YOUTUBE, ParsingHelper::getVideoTypeFromUrl('https://www.youtube.com'));
        $this->assertEquals(VideoType::VIMEO, ParsingHelper::getVideoTypeFromUrl('https://vimeo.com/12345'));
        $this->assertEquals(VideoType::UNKNOWN, ParsingHelper::getVideoTypeFromUrl('https://example.com'));
    }

    public function testGetVideoTypeFromUrlReturnUnknownForMalformedInput(): void
    {
        $this->assertEquals(VideoType::UNKNOWN, ParsingHelper::getVideoTypeFromUrl(''));
        $this->assertEquals(VideoType::UNKNOWN, ParsingHelper::getVideoTypeFromUrl('not a url'));
    }

    /**
     * Non-http/https schemes must return UNKNOWN before any host or ID
     * extraction happens.
     */
    public function testGetVideoTypeFromUrlRejectsNonHttpSchemes(): void
    {
        $this->assertEquals(
            VideoType::UNKNOWN,
            ParsingHelper::getVideoTypeFromUrl('javascript://youtube.com/watch?v=ID'),
            'javascript: scheme must be rejected'
        );

        $this->assertEquals(
            VideoType::UNKNOWN,
            ParsingHelper::getVideoTypeFromUrl('ftp://youtube.com/watch?v=ID'),
            'ftp: scheme must be rejected'
        );

        $this->assertEquals(
            VideoType::UNKNOWN,
            ParsingHelper::getVideoTypeFromUrl('//youtube.com/watch?v=ID'),
            'protocol-relative URL has no scheme and must be rejected'
        );
    }

    // =========================================================================
    // getVideoTypeFromUrl — host validation
    // =========================================================================

    /**
     * The previous implementation used strripos() which matches the target
     * string anywhere in the URL. Exact host matching must be used instead.
     */
    public function testGetVideoTypeFromUrlRejectsFakeHosts(): void
    {
        $this->assertEquals(
            VideoType::UNKNOWN,
            ParsingHelper::getVideoTypeFromUrl('https://notyoutube.com/watch?v=ID'),
            'notyoutube.com contains "youtube.com" as substring — must be UNKNOWN'
        );

        $this->assertEquals(
            VideoType::UNKNOWN,
            ParsingHelper::getVideoTypeFromUrl('https://attackervimeo.com/12345'),
            'attackervimeo.com contains "vimeo.com" as substring — must be UNKNOWN'
        );

        $this->assertEquals(
            VideoType::UNKNOWN,
            ParsingHelper::getVideoTypeFromUrl('https://evil.com/?r=https://youtube.com/watch?v=ID'),
            'youtube.com in query string only — host is evil.com, must be UNKNOWN'
        );
    }

    // =========================================================================
    // VideoData — YouTube output properties
    // =========================================================================

    /**
     * Verify the url property is a valid YouTube watch URL.
     * Previously built with ?= instead of ?v=, producing a 404.
     * Also verify embedUrl includes rel=0 to restrict related videos to the
     * same channel (YouTube removed full suppression in September 2018).
     */
    public function testForYoutubeOutputProperties(): void
    {
        $video = VideoData::forYoutube('dQw4w9WgXcQ');
        $this->assertNotNull($video);
        $this->assertEquals('https://www.youtube.com/watch?v=dQw4w9WgXcQ', $video->url);
        $this->assertEquals('https://www.youtube.com/embed/dQw4w9WgXcQ?rel=0', $video->embedUrl);
        $this->assertEquals('https://i.ytimg.com/vi/dQw4w9WgXcQ/hqdefault.jpg', $video->image);
    }

    // =========================================================================
    // getYouTubeIdFromUrl — query param formats
    // =========================================================================

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

    // =========================================================================
    // getYouTubeIdFromUrl — two-segment path formats
    // =========================================================================

    /**
     * YouTube Shorts URLs use /shorts/ID. The previous path fallback took the
     * first segment ("shorts"), producing embed URL .../embed/shorts (a 404).
     */
    public function testGetYouTubeIdFromUrlShorts(): void
    {
        $this->assertEquals(
            'dQw4w9WgXcQ',
            ParsingHelper::getYouTubeIdFromUrl('https://www.youtube.com/shorts/dQw4w9WgXcQ'),
            '/shorts/ URL — ID must be the segment after /shorts/'
        );

        $this->assertEquals(
            'dQw4w9WgXcQ',
            ParsingHelper::getYouTubeIdFromUrl('https://youtube.com/shorts/dQw4w9WgXcQ?feature=share'),
            '/shorts/ URL with query string'
        );
    }

    /**
     * YouTube Live URLs use /live/ID.
     */
    public function testGetYouTubeIdFromUrlLive(): void
    {
        $this->assertEquals(
            'dQw4w9WgXcQ',
            ParsingHelper::getYouTubeIdFromUrl('https://www.youtube.com/live/dQw4w9WgXcQ'),
            '/live/ URL — ID must be the segment after /live/'
        );
    }

    /**
     * Embed URLs (/embed/ID) are valid inputs — some CMS fields store the
     * embed URL rather than the watch URL.
     */
    public function testGetYouTubeIdFromUrlEmbed(): void
    {
        $this->assertEquals(
            'dQw4w9WgXcQ',
            ParsingHelper::getYouTubeIdFromUrl('https://www.youtube.com/embed/dQw4w9WgXcQ'),
            '/embed/ URL — ID must be the segment after /embed/'
        );
    }

    /**
     * Legacy /v/ID format.
     */
    public function testGetYouTubeIdFromUrlLegacyV(): void
    {
        $this->assertEquals(
            'dQw4w9WgXcQ',
            ParsingHelper::getYouTubeIdFromUrl('https://www.youtube.com/v/dQw4w9WgXcQ'),
            '/v/ URL — ID must be the segment after /v/'
        );
    }

    // =========================================================================
    // getYouTubeIdFromUrl — ID character validation
    // =========================================================================

    /**
     * YouTube IDs must match [A-Za-z0-9_-]+. Characters outside that set must
     * cause the extractor to return null so the value is never interpolated into
     * embed URLs or image URLs.
     */
    public function testGetYouTubeIdFromUrlRejectsInjectionPayloads(): void
    {
        $this->assertNull(
            ParsingHelper::getYouTubeIdFromUrl('https://youtu.be/VALID_ID" onload="alert(1)'),
            'Double-quote in path must return null'
        );

        $this->assertNull(
            ParsingHelper::getYouTubeIdFromUrl('https://www.youtube.com/watch?v=<script>alert(1)</script>'),
            'Angle brackets in v= must return null'
        );

        // Dot-segment as the first path component. parse_url does not normalize
        // paths, so youtu.be/../evil has ".." as segment 0, which contains "."
        // and is not in [A-Za-z0-9_-].
        $this->assertNull(
            ParsingHelper::getYouTubeIdFromUrl('https://youtu.be/../evil'),
            'Dot-segment as first path component must return null'
        );

        $this->assertNull(
            ParsingHelper::getYouTubeIdFromUrl('https://youtu.be/VALID ID'),
            'Space in ID must return null'
        );
    }

    // =========================================================================
    // getVimeoIdFromUrl
    // =========================================================================

    public function testGetVimeoIdFromUrlBasic(): void
    {
        $this->assertEquals('12345', ParsingHelper::getVimeoIdFromUrl('https://vimeo.com/12345'));
    }

    /**
     * Privacy hash is a second segment after the numeric ID and must not
     * interfere with ID extraction.
     */
    public function testGetVimeoIdFromUrlWithPrivacyHash(): void
    {
        $this->assertEquals(
            '12345',
            ParsingHelper::getVimeoIdFromUrl('https://vimeo.com/12345/abcdef1234'),
            'Numeric ID with trailing privacy hash'
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

    // =========================================================================
    // getVimeoIdFromUrl — multi-segment URL formats
    // =========================================================================

    /**
     * Channel URLs: vimeo.com/channels/<slug>/<video-id>
     * Previously returned "channels" — the first segment, not the ID.
     */
    public function testGetVimeoIdFromUrlChannels(): void
    {
        $this->assertEquals(
            '12345',
            ParsingHelper::getVimeoIdFromUrl('https://vimeo.com/channels/staffpicks/12345'),
            'Channel URL — must return the numeric ID, not "channels"'
        );

        $this->assertEquals(
            '12345',
            ParsingHelper::getVimeoIdFromUrl('https://vimeo.com/channels/mychannel/12345')
        );
    }

    /**
     * Group URLs: vimeo.com/groups/<slug>/videos/<video-id>
     * Previously returned "groups".
     */
    public function testGetVimeoIdFromUrlGroups(): void
    {
        $this->assertEquals(
            '12345',
            ParsingHelper::getVimeoIdFromUrl('https://vimeo.com/groups/shortfilms/videos/12345'),
            'Group URL — must return the numeric ID, not "groups"'
        );
    }

    /**
     * /video/ID prefix format. Previously returned "video".
     */
    public function testGetVimeoIdFromUrlVideoPrefix(): void
    {
        $this->assertEquals(
            '12345',
            ParsingHelper::getVimeoIdFromUrl('https://vimeo.com/video/12345'),
            '/video/ URL — must return the numeric ID, not "video"'
        );
    }

    // =========================================================================
    // getVimeoIdFromUrl — ID validation
    // =========================================================================

    /**
     * Vimeo IDs are purely numeric. Non-numeric first segments and XSS
     * payloads in the path must return null.
     */
    public function testGetVimeoIdFromUrlRejectsNonNumericAndPayloads(): void
    {
        $this->assertNull(
            ParsingHelper::getVimeoIdFromUrl('https://vimeo.com/channels'),
            'Non-numeric-only path must return null'
        );

        $this->assertNull(
            ParsingHelper::getVimeoIdFromUrl('https://vimeo.com/12345" onload="alert(1)'),
            'Double-quote breaking segment boundary must return null'
        );
    }

    // =========================================================================
    // getVimeoHashFromUrl
    // =========================================================================

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

    // =========================================================================
    // getVideoDataFromUrl — embedUrl and canonical URL
    // =========================================================================

    /**
     * VideoEmbedService::getEmbedUrl() is a thin wrapper over getVideoData()->embedUrl.
     * Testing the embedUrl property here exercises the same code path without
     * requiring a full Craft bootstrap to instantiate the service.
     */
    public function testGetVideoDataFromUrlReturnsEmbedUrl(): void
    {
        $data = ParsingHelper::getVideoDataFromUrl('https://www.youtube.com/watch?v=6xWpo5Dn254');
        $this->assertNotNull($data);
        $this->assertStringStartsWith('https://www.youtube.com/embed/', $data->embedUrl);
        $this->assertStringContainsString('6xWpo5Dn254', $data->embedUrl);

        $data = ParsingHelper::getVideoDataFromUrl('https://vimeo.com/12345');
        $this->assertNotNull($data);
        $this->assertStringStartsWith('https://player.vimeo.com/video/', $data->embedUrl);
        $this->assertStringContainsString('12345', $data->embedUrl);
    }

    public function testGetVideoDataFromUrlReturnsNullForUnknownUrl(): void
    {
        $this->assertNull(ParsingHelper::getVideoDataFromUrl('https://example.com/video'));
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
}
