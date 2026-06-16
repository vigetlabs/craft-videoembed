<?php

namespace viget\videoembed\helpers;

use viget\videoembed\enums\VideoType;
use viget\videoembed\models\VideoData;

class ParsingHelper
{
    const YOUTUBE_URLS = ['youtube.com', 'youtu.be'];
    const VIMEO_URLS = ['vimeo.com', 'player.vimeo.com'];

    /** The YouTube path prefix that identifies a vertical Shorts video. */
    const YOUTUBE_SHORTS_PREFIX = 'shorts';

    public static function getVideoDataFromUrl(string $url): ?VideoData
    {
        return match(self::getVideoTypeFromUrl($url)) {
            VideoType::YOUTUBE => VideoData::forYoutube(
                self::getYouTubeIdFromUrl($url),
                self::isYouTubeShortsUrl($url)
            ),
            VideoType::VIMEO => VideoData::forVimeo(
                self::getVimeoIdFromUrl($url),
                self::getVimeoHashFromUrl($url)
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

            $v = $qs['v'] ?? null;
            $vi = $qs['vi'] ?? null;
            
            if ($v || $vi) {
                return $v ?? $vi;
            }
        }

        /**
         * Patterns:
         * - https://www.youtube.com/watch?v=--HXLM8GuxA
         * - https://youtu.be/--HXLM8GuxA?si=pNahKJLszKr8J00u
         * - https://www.youtube.com/shorts/--HXLM8GuxA
         */
        if ($path) {
            $explodedPath = explode('/', trim($path, '/'));

            // YouTube Shorts URLs are /shorts/{id} — the ID is the second
            // segment. Return null (not "shorts") when the ID is absent.
            if (strtolower($explodedPath[0] ?? '') === self::YOUTUBE_SHORTS_PREFIX) {
                return $explodedPath[1] ?? null;
            }

            return $explodedPath[0] ?? null;
        }

        return null;
    }

    /**
     * Determines whether a URL is a YouTube Shorts URL (path begins with
     * /shorts/). Shorts are always vertical (portrait) videos, so this drives
     * VideoData::$isVertical.
     *
     * Assumes the URL has already been identified as YouTube; it inspects only
     * the path. Detection is based on URL form — the plugin cannot determine a
     * video's true pixel orientation without an external API call.
     */
    private static function isYouTubeShortsUrl(string $url): bool
    {
        $path = parse_url($url, PHP_URL_PATH);

        if (!$path) {
            return false;
        }

        $segments = explode('/', trim($path, '/'));

        return strtolower($segments[0] ?? '') === self::YOUTUBE_SHORTS_PREFIX;
    }
    
    /**
     * Gets the numeric video ID from a Vimeo URL.
     *
     * The ID's position depends on the URL form, so it is located structurally
     * rather than by grabbing the first numeric run (a numeric slug would win —
     * see issue #35). Handles:
     * - vimeo.com/{id}                          (id = first segment)
     * - vimeo.com/{id}/{hash}                   (id = first segment; hash may be numeric)
     * - vimeo.com/channels/{slug}/{id}          (id = last segment)
     * - vimeo.com/album/{slug}/{id}             (id = last segment)
     * - vimeo.com/groups/{slug}/videos/{id}     (id = segment after "videos")
     * - vimeo.com/showcase/{slug}/video/{id}    (id = segment after "video")
     * - vimeo.com/video/{id}                    (id = segment after "video")
     * - player.vimeo.com/video/{id}             (id = segment after "video")
     */
    public static function getVimeoIdFromUrl(string $url): ?string
    {
        $parts = parse_url($url);
        $path = $parts['path'] ?? null;

        if (!$path) {
            return null;
        }

        $segments = array_values(
            array_filter(explode('/', $path), static fn($s) => $s !== '')
        );

        if (!$segments) {
            return null;
        }

        // /video/{id} and /groups/{slug}/videos/{id} — the ID follows the last
        // "video"/"videos" marker.
        $markerIndex = null;
        foreach ($segments as $i => $segment) {
            if ($segment === 'video' || $segment === 'videos') {
                $markerIndex = $i;
            }
        }
        if ($markerIndex !== null) {
            return self::numericOrNull($segments[$markerIndex + 1] ?? null);
        }

        // /channels/{slug}/{id}, /showcase/{slug}/{id}, /album/{slug}/{id} —
        // the ID is the trailing segment after the named collection.
        if (in_array($segments[0], ['channels', 'showcase', 'album'], true)) {
            return self::numericOrNull(end($segments));
        }

        // Root form: /{id} or /{id}/{hash} — the ID is the first segment, and a
        // trailing hash (which may itself be numeric) is not the ID.
        return self::numericOrNull($segments[0]);
    }

    /**
     * Returns the value only when it is a purely-numeric string (a Vimeo video
     * ID), otherwise null.
     */
    private static function numericOrNull(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        return preg_match('/^\d+$/', $value) ? $value : null;
    }

    /**
     * Gets the private hash from a Vimeo URL.
     * Handles vimeo.com/{id}/{hash} and player.vimeo.com/video/{id}?h={hash} formats.
     */
    public static function getVimeoHashFromUrl(string $url): ?string
    {
        $parts = parse_url($url);

        // player.vimeo.com uses ?h= query param
        if (isset($parts['query'])) {
            parse_str($parts['query'], $qs);
            if (!empty($qs['h'])) {
                return $qs['h'];
            }
        }

        $path = $parts['path'] ?? null;

        if (!$path) {
            return null;
        }

        $segments = explode('/', trim($path, '/'));

        // A path hash only appears as the second segment of vimeo.com/{id}/{hash}.
        // Channel/group URLs lead with a named slug and player.vimeo.com paths
        // lead with "video" — neither is numeric, so there is no path hash.
        if (!preg_match('/^\d+$/', $segments[0] ?? '')) {
            return null;
        }

        return $segments[1] ?? null;
    }

}