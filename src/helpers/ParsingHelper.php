<?php

namespace viget\videoembed\helpers;

use viget\videoembed\enums\VideoType;
use viget\videoembed\models\VideoData;

class ParsingHelper
{
    const YOUTUBE_URLS = ['youtube.com', 'youtu.be'];
    const VIMEO_URLS = ['vimeo.com'];

    /**
     * YouTube path prefixes where the video ID is the second path segment.
     * e.g. /shorts/ID, /live/ID, /embed/ID, /v/ID
     */
    const YOUTUBE_TWO_SEGMENT_PATHS = ['shorts', 'live', 'embed', 'v'];

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
     * Gets the video ID from a YouTube URL.
     *
     * Handles:
     * - https://www.youtube.com/watch?v=ID  (query param v=)
     * - https://www.youtube.com/watch?vi=ID (query param vi=)
     * - https://youtu.be/ID                 (short URL, ID is path segment 0)
     * - https://youtube.com/shorts/ID       (Shorts, ID is path segment 1)
     * - https://youtube.com/live/ID         (Live, ID is path segment 1)
     * - https://youtube.com/embed/ID        (embed URL, ID is path segment 1)
     * - https://youtube.com/v/ID            (legacy /v/ URL, ID is path segment 1)
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

        if ($path) {
            $explodedPath = explode('/', trim($path, '/'));

            // Paths like /shorts/ID, /live/ID, /embed/ID, /v/ID — the ID is
            // the second segment, not the first.
            if (
                isset($explodedPath[1]) &&
                in_array($explodedPath[0], self::YOUTUBE_TWO_SEGMENT_PATHS)
            ) {
                return $explodedPath[1];
            }

            // youtu.be/ID and any other single-segment path format
            return $explodedPath[0] ?? null;
        }

        return null;
    }

    /**
     * Gets the video ID from a Vimeo URL.
     *
     * Handles:
     * - https://vimeo.com/12345              (root path)
     * - https://vimeo.com/12345/privacy-hash (root path with trailing hash)
     * - https://vimeo.com/channels/X/12345   (channel URL)
     * - https://vimeo.com/groups/name/videos/12345 (group URL)
     * - https://vimeo.com/video/12345        (/video/ prefix)
     *
     * Vimeo video IDs are always numeric. This method finds the first purely
     * numeric path segment, which is the video ID in all known URL formats.
     */
    public static function getVimeoIdFromUrl(string $url): ?string
    {
        $parts = parse_url($url);
        $path = $parts['path'] ?? null;

        if (!$path) {
            return null;
        }

        // Find the first path segment that is purely numeric — that is the
        // video ID regardless of how many named prefix segments precede it.
        if (preg_match('/\/(\d+)(?:\/|$)/', $path, $matches)) {
            return $matches[1];
        }

        return null;
    }

}
