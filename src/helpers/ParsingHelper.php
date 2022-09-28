<?php

namespace viget\videoembed\helpers;

use viget\videoembed\enums\VideoType;
use viget\videoembed\models\VideoData;

class ParsingHelper
{
    const YOUTUBE_URLS = ['youtube.com', 'youtu.be'];
    const VIMEO_URLS = ['vimeo.com'];
    
    public static function getVideoDataFromUrl(string $url): ?VideoData
    {
        return match(self::getVideoTypeFromUrl($url)) {
            VideoType::YOUTUBE => VideoData::forYoutube(
                self::getYouTubeIdFromUrl($url)
            ),
            VideoType::VIMEO => VideoData::forVimeo(
                self::getVimeoIdFromUrl($url)
            ),
            default => null,
        };
    }
    
    public static function getVideoTypeFromUrl(string $url): VideoType
    {
        $parsedUrl = parse_url($url);
        $host = $parsedUrl['host'] ?? null;
        if (!$host) {
            return VideoType::UNKNOWN;
        }
        
        $host = str_replace('www.', '', $host);
        $host = strtolower($host);
        
        if (in_array($host, self::YOUTUBE_URLS)) {
            return VideoType::YOUTUBE;
        }
        
        if (in_array($host, self::VIMEO_URLS)) {
            return VideoType::VIMEO;
        }
        
        return VideoType::UNKNOWN;
    }
    
    /**
     * Gets the video id from a YouTube URL
     */
    public static function getYouTubeIdFromUrl(string $url): ?string
    {
        if (empty($url)) {
            return null;
        }
        
        $parts = parse_url($url);
        $query = $parts['query'] ?? null;
        $path = $parts['path'] ?? null;
        
        if ($query) {
            parse_str($query, $qs);
            return $qs['v'] ?? $qs['vi'] ?? null;
        }
    
        // Deals with https://youtu.be/X9tg3J5OiYU URLs
        if ($path) {
            $explodedPath = explode('/', trim($path, '/'));
            return $explodedPath[0] ?? null;
        }
        
        return null;
    }
    
    /**
     * Gets the video id from a Vimeo URL
     */
    public static function getVimeoIdFromUrl(string $url): ?string
    {
        $parts = parse_url($url);
        $path = $parts['path'] ?? null;
        
        if (!$path) {
            return null;
        }
        
        $segments = explode('/', trim($path, '/'));
    
        return $segments[0] ?? null;
    }

}