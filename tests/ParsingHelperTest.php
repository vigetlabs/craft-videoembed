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

    public function testGetVimeoIdFromUrlChannels(): void
    {
        $this->assertEquals(
            '12345',
            ParsingHelper::getVimeoIdFromUrl('https://vimeo.com/channels/staffpicks/12345'),
            'Channel URL — must return the numeric ID, not "channels"'
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
}