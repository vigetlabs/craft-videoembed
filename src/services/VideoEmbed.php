<?php

namespace viget\videoembed\services;

use craft\base\Component;
use viget\videoembed\enums\VideoType;
use viget\videoembed\helpers\ParsingHelper;
use viget\videoembed\models\VideoData;

class VideoEmbed extends Component
{

    /**
     * Takes a YouTube or Vimeo URL and returns metadata for the video
     */
    public function getVideoData(string $url): ?VideoData
    {
        return ParsingHelper::getVideoDataFromUrl($url);
    }

    /**
     * Takes a YouTube or Vimeo URL and returns the embed URL string, or null if
     * the URL cannot be parsed. Convenience wrapper over getVideoData() for
     * callers that only need the iframe src.
     */
    public function getEmbedUrl(string $url): ?string
    {
        return $this->getVideoData($url)?->embedUrl;
    }

    /**
     * Determine whether the url is a YouTube or Vimeo url
     */
    public function isVideoUrl(string $url): bool
    {
        return ParsingHelper::getVideoTypeFromUrl($url) !== VideoType::UNKNOWN;
    }
}
