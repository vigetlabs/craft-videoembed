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
    )
    {
    }
    
    public static function forYoutube(?string $youtubeId): ?static
    {
        if (!$youtubeId) {
            return null;
        }
        
        return new self(
            type: VideoType::YOUTUBE->value,
            id: $youtubeId,
            image: "https://i.ytimg.com/vi/{$youtubeId}/hqdefault.jpg",
            embedUrl: "https://www.youtube.com/embed/{$youtubeId}",
            url: "https://www.youtube.com/watch?={$youtubeId}",
        );
    }
    
    public static function forVimeo(?string $vimeoId): ?static
    {
        if (!$vimeoId) {
            return null;
        }
        
        return new self(
            type: VideoType::VIMEO->value,
            id: $vimeoId,
            image: null, // TODO there isn't an easy way to get this without querying an API or oEmbed endpoint
            embedUrl: "https://player.vimeo.com/video/{$vimeoId}",
            url: "https://www.vimeo.com/{$vimeoId}",
        );
    }
}