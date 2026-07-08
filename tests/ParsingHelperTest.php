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

    public function testGetVideoTypeFromUrlReturnsUnknownForMalformedInput(): void
    {
        $this->assertEquals(VideoType::UNKNOWN, ParsingHelper::getVideoTypeFromUrl(''));
        $this->assertEquals(VideoType::UNKNOWN, ParsingHelper::getVideoTypeFromUrl('not a url'));
    }

    /**
     * Non-http/https schemes must return UNKNOWN before the host is trusted.
     */
    public function testGetVideoTypeFromUrlRejectsNonHttpSchemes(): void
    {
        $this->assertEquals(
            VideoType::UNKNOWN,
            ParsingHelper::getVideoTypeFromUrl('javascript://youtube.com/watch?v=ID')
        );
        $this->assertEquals(
            VideoType::UNKNOWN,
            ParsingHelper::getVideoTypeFromUrl('ftp://youtube.com/watch?v=ID')
        );
        $this->assertEquals(
            VideoType::UNKNOWN,
            ParsingHelper::getVideoTypeFromUrl('//youtube.com/watch?v=ID')
        );
    }

    /**
     * Exact host matching: a domain that merely contains "youtube.com" or
     * "vimeo.com" as a substring must not be treated as that provider.
     */
    public function testGetVideoTypeFromUrlRejectsFakeHosts(): void
    {
        $this->assertEquals(
            VideoType::UNKNOWN,
            ParsingHelper::getVideoTypeFromUrl('https://notyoutube.com/watch?v=ID')
        );
        $this->assertEquals(
            VideoType::UNKNOWN,
            ParsingHelper::getVideoTypeFromUrl('https://attackervimeo.com/12345')
        );
        $this->assertEquals(
            VideoType::UNKNOWN,
            ParsingHelper::getVideoTypeFromUrl('https://evil.com/?r=https://youtube.com/watch?v=ID')
        );
    }

    /**
     * Mobile (m.) and YouTube Music (music.) subdomains must still resolve to
     * YouTube (issue #36).
     */
    public function testGetVideoTypeFromUrlAcceptsYouTubeSubdomains(): void
    {
        $this->assertEquals(
            VideoType::YOUTUBE,
            ParsingHelper::getVideoTypeFromUrl('https://m.youtube.com/watch?v=ID')
        );
        $this->assertEquals(
            VideoType::YOUTUBE,
            ParsingHelper::getVideoTypeFromUrl('https://music.youtube.com/watch?v=ID')
        );
    }

    /**
     * IDs containing characters outside [A-Za-z0-9_-] must return null so they
     * are never interpolated into embed or image URLs.
     */
    public function testGetYouTubeIdFromUrlRejectsInjectionPayloads(): void
    {
        $this->assertNull(
            ParsingHelper::getYouTubeIdFromUrl('https://youtu.be/VALID_ID" onload="alert(1)')
        );
        $this->assertNull(
            ParsingHelper::getYouTubeIdFromUrl('https://www.youtube.com/watch?v=<script>alert(1)</script>')
        );
        $this->assertNull(
            ParsingHelper::getYouTubeIdFromUrl('https://youtu.be/VALID ID')
        );
        $this->assertNull(
            ParsingHelper::getYouTubeIdFromUrl('https://youtu.be/../evil')
        );
    }

    /**
     * Array-valued query params (?v[]=) must not throw a TypeError (issue #34).
     */
    public function testGetYouTubeIdFromUrlRejectsArrayQueryParam(): void
    {
        $this->assertNull(
            ParsingHelper::getYouTubeIdFromUrl('https://www.youtube.com/watch?v[]=abc')
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

    public function testGetVimeoIdFromUrlChannels(): void
    {
        $this->assertEquals(
            '12345',
            ParsingHelper::getVimeoIdFromUrl('https://vimeo.com/channels/staffpicks/12345'),
            'Channel URL — must return the numeric ID, not "channels"'
        );

        $this->assertEquals(
            '12345',
            ParsingHelper::getVimeoIdFromUrl('https://vimeo.com/album/myalbum/12345'),
            'Album URL — ID is the trailing segment'
        );
    }

    public function testGetVimeoIdFromUrlGroups(): void
    {
        $this->assertEquals(
            '12345',
            ParsingHelper::getVimeoIdFromUrl('https://vimeo.com/groups/shortfilms/videos/12345'),
            'Group URL — must return the numeric ID, not "groups"'
        );
    }

    public function testGetVimeoIdFromUrlVideoPrefix(): void
    {
        $this->assertEquals(
            '12345',
            ParsingHelper::getVimeoIdFromUrl('https://vimeo.com/video/12345')
        );
    }

    /**
     * Numeric slugs must not be mistaken for the video ID (issue #35). The ID
     * is located structurally, so a numeric channel/group/showcase slug
     * preceding the real ID is ignored.
     */
    public function testGetVimeoIdFromUrlNumericSlugs(): void
    {
        $this->assertEquals(
            '456',
            ParsingHelper::getVimeoIdFromUrl('https://vimeo.com/groups/123/videos/456'),
            'Numeric group slug — ID is the segment after "videos"'
        );

        $this->assertEquals(
            '12345',
            ParsingHelper::getVimeoIdFromUrl('https://vimeo.com/channels/2020/12345'),
            'Numeric channel slug — ID is the trailing segment'
        );

        $this->assertEquals(
            '12345',
            ParsingHelper::getVimeoIdFromUrl('https://vimeo.com/showcase/7654321/video/12345'),
            'Numeric showcase slug — ID is the segment after "video"'
        );
    }

    /**
     * A numeric privacy hash in the root /{id}/{hash} form must not be returned
     * as the ID — the ID is always the first segment here.
     */
    public function testGetVimeoIdFromUrlNumericHashKeepsId(): void
    {
        $this->assertEquals(
            '9999999999',
            ParsingHelper::getVimeoIdFromUrl('https://vimeo.com/9999999999/0000000000')
        );
    }

    public function testGetVimeoIdFromUrlRejectsNonNumeric(): void
    {
        $this->assertNull(
            ParsingHelper::getVimeoIdFromUrl('https://vimeo.com/channels')
        );
    }

    public function testGetVimeoHashFromUrlChannelHasNoPathHash(): void
    {
        $this->assertNull(
            ParsingHelper::getVimeoHashFromUrl('https://vimeo.com/channels/staffpicks/12345')
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
        $this->assertEquals('https://www.youtube.com/embed/dQw4w9WgXcQ?rel=0', $video->embedUrl);
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

    /**
     * YouTube embed URLs include rel=0 so related-video suggestions stay on the
     * same channel (YouTube removed full suppression in September 2018).
     */
    public function testGetYouTubeEmbedUrlIncludesRel0(): void
    {
        $this->assertEquals(
            'https://www.youtube.com/embed/dQw4w9WgXcQ?rel=0',
            ParsingHelper::getVideoDataFromUrl('https://www.youtube.com/watch?v=dQw4w9WgXcQ')->embedUrl
        );
    }

    // =========================================================================
    // Coverage baseline: parse_url() guards + defect characterization (#53)
    //
    // The characterization tests below assert the CURRENT behavior of known
    // defects so their fixes land as legible assertion flips. They are not an
    // endorsement of the behavior — each is tagged with the ticket that changes
    // it, and that fix's PR will invert the assertion.
    // =========================================================================

    /**
     * The !is_array(parse_url(...)) guards added in #42 return the empty result
     * for input where parse_url() itself fails. "http://" is such an input
     * (parse_url() returns false), so it exercises both guard branches.
     */
    public function testMalformedUrlHitsParseUrlGuards(): void
    {
        $this->assertEquals(
            VideoType::UNKNOWN,
            ParsingHelper::getVideoTypeFromUrl('http://')
        );
        $this->assertNull(
            ParsingHelper::getYouTubeIdFromUrl('http://')
        );
    }

    /**
     * Characterizes current behavior — flipped by #49.
     *
     * An array-valued ?h[]= param makes parse_str() yield an array, which
     * violates the ?string return type and throws a TypeError. #49 will guard the
     * value and return null instead.
     */
    public function testCharacterizeVimeoHashArrayParamThrows(): void
    {
        $this->expectException(\TypeError::class);
        ParsingHelper::getVimeoHashFromUrl('https://player.vimeo.com/video/123?h[]=abc');
    }

    /**
     * Characterizes current behavior — flipped by #49.
     *
     * The path-form hash is returned without charset validation, so injection
     * characters flow through untouched into the embed URL. #49 will validate the
     * hash charset and return null for this input.
     */
    public function testCharacterizeVimeoHashSkipsCharsetValidation(): void
    {
        $this->assertEquals(
            'abc"onload',
            ParsingHelper::getVimeoHashFromUrl('https://vimeo.com/12345/abc"onload')
        );
    }

    /**
     * Characterizes current behavior — flipped by #50.
     *
     * With no ?v= present, the first path segment is trusted as the video ID, so
     * reserved routes are returned verbatim as bogus IDs. #50 will treat
     * embed/live like shorts (ID is the second segment) and return null for
     * non-ID routes such as watch/playlist.
     */
    public function testCharacterizeReservedPathSegmentsReturnedAsIds(): void
    {
        $this->assertEquals(
            'embed',
            ParsingHelper::getYouTubeIdFromUrl('https://www.youtube.com/embed/dQw4w9WgXcQ')
        );
        $this->assertEquals(
            'live',
            ParsingHelper::getYouTubeIdFromUrl('https://www.youtube.com/live/dQw4w9WgXcQ')
        );
        $this->assertEquals(
            'watch',
            ParsingHelper::getYouTubeIdFromUrl('https://www.youtube.com/watch')
        );
        $this->assertEquals(
            'playlist',
            ParsingHelper::getYouTubeIdFromUrl('https://www.youtube.com/playlist?list=PL1')
        );
    }

    /**
     * Characterizes current behavior — flipped by #52.
     *
     * The host prefix strip removes only one leading label, so a stacked prefix
     * (www.m.youtube.com) falls through to UNKNOWN. #52 will strip stacked
     * prefixes while keeping the anchored (non-substring) match.
     */
    public function testCharacterizeStackedHostPrefixRejected(): void
    {
        $this->assertEquals(
            VideoType::UNKNOWN,
            ParsingHelper::getVideoTypeFromUrl('https://www.m.youtube.com/watch?v=abc')
        );
    }

    /**
     * Characterizes current behavior — flipped by #51.
     *
     * A scheme-less URL yields no host under parse_url(), so it is rejected. #51
     * will normalize scheme-less, host-like input by prepending https:// before
     * parsing (javascript:/ftp: still carry a scheme and stay rejected).
     */
    public function testCharacterizeSchemelessUrlRejected(): void
    {
        $this->assertEquals(
            VideoType::UNKNOWN,
            ParsingHelper::getVideoTypeFromUrl('www.youtube.com/watch?v=abc')
        );
    }
}
