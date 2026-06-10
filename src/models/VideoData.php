<?php

namespace viget\videoembed\models;

use viget\videoembed\enums\VideoType;

class VideoData
{
    public function __construct(
        public string $type,
        public string $id,
        public ?string $image,
        public string $embedUrl,
        public string $url,
        public bool $isVertical = false,
    )
    {
    }

    public static function forYoutube(?string $youtubeId, bool $isVertical = false): ?static
    {
        if (!$youtubeId) {
            return null;
        }

        return new self(
            type: VideoType::YOUTUBE->value,
            id: $youtubeId,
            image: "https://i.ytimg.com/vi/{$youtubeId}/hqdefault.jpg",
            embedUrl: "https://www.youtube.com/embed/{$youtubeId}",
            url: "https://www.youtube.com/watch?v={$youtubeId}",
            isVertical: $isVertical,
        );
    }

    public static function forVimeo(?string $vimeoId, ?string $vimeoHash = null): ?static
    {
        if (!$vimeoId) {
            return null;
        }

        $embedUrl = "https://player.vimeo.com/video/{$vimeoId}";
        $canonicalUrl = "https://www.vimeo.com/{$vimeoId}";

        if ($vimeoHash) {
            $embedUrl .= "?h={$vimeoHash}";
            $canonicalUrl .= "/{$vimeoHash}";
        }

        return new self(
            type: VideoType::VIMEO->value,
            id: $vimeoId,
            image: null, // TODO there isn't an easy way to get this without querying an API or oEmbed endpoint
            embedUrl: $embedUrl,
            url: $canonicalUrl,
        );
    }
}
