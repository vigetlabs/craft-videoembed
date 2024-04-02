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
}