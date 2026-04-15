<?php

use PHPUnit\Framework\TestCase;
use viget\videoembed\enums\VideoType;
use viget\videoembed\helpers\ParsingHelper;
use viget\videoembed\models\VideoData;

final class ParsingHelperTest extends TestCase
{
    // =========================================================================
    // getVideoTypeFromUrl
    // =========================================================================

    public function testGetVideoTypeFromUrl(): void
    {
        $this->assertEquals(VideoType::YOUTUBE, ParsingHelper::getVideoTypeFromUrl('https://www.youtube.com'));
        $this->assertEquals(VideoType::VIMEO, ParsingHelper::getVideoTypeFromUrl('https://vimeo.com/12345'));
        $this->assertEquals(VideoType::UNKNOWN, ParsingHelper::getVideoTypeFromUrl('https://example.com'));
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
    // getVimeoIdFromUrl — existing formats
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
     * /video/ID prefix format.
     * Previously returned "video".
     */
    public function testGetVimeoIdFromUrlVideoPrefix(): void
    {
        $this->assertEquals(
            '12345',
            ParsingHelper::getVimeoIdFromUrl('https://vimeo.com/video/12345'),
            '/video/ URL — must return the numeric ID, not "video"'
        );
    }
}
