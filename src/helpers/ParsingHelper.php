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

        // parse_url() returns false on seriously malformed input.
        if (!is_array($parsedUrl)) {
            return VideoType::UNKNOWN;
        }

        // Only http/https are valid video URLs. Reject javascript:, ftp:, etc.
        // and protocol-relative URLs (no scheme) before trusting the host.
        $scheme = strtolower($parsedUrl['scheme'] ?? '');
        if (!in_array($scheme, ['http', 'https'], true)) {
            return VideoType::UNKNOWN;
        }

        $host = $parsedUrl['host'] ?? null;
        if (!$host) {
            return VideoType::UNKNOWN;
        }

        // Strip a leading www. or a known YouTube subdomain (m., music.) so
        // mobile and YouTube Music links still resolve (issue #36). Only a
        // leading prefix is removed — a substring match would let e.g.
        // "notyoutube.com" through.
        $host = preg_replace('/^(www|m|music)\./', '', strtolower($host));

        if (in_array($host, self::YOUTUBE_URLS, true)) {
            return VideoType::YOUTUBE;
        }

        if (in_array($host, self::VIMEO_URLS, true)) {
            return VideoType::VIMEO;
        }

        return VideoType::UNKNOWN;
    }
    
    /**
     * Gets the video id from a YouTube URL.
     *
     * Patterns:
     * - https://www.youtube.com/watch?v=--HXLM8GuxA
     * - https://www.youtube.com/watch?vi=--HXLM8GuxA
     * - https://youtu.be/--HXLM8GuxA?si=pNahKJLszKr8J00u
     */
    public static function getYouTubeIdFromUrl(string $url): ?string
    {
        if (empty($url)) {
            return null;
        }

        $parts = parse_url($url);
        if (!is_array($parts)) {
            return null;
        }

        $query = $parts['query'] ?? null;
        $path = $parts['path'] ?? null;

        if ($query) {
            parse_str($query, $qs);

            // When a v/vi key is present it owns the result. parse_str yields an
            // array for ?v[]= style params; those are not valid IDs (and would
            // be a TypeError against the ?string return — issue #34), so reject.
            if (array_key_exists('v', $qs) || array_key_exists('vi', $qs)) {
                $id = $qs['v'] ?? $qs['vi'] ?? null;
                return is_string($id) ? self::validateYouTubeId($id) : null;
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
                return self::validateYouTubeId($explodedPath[1] ?? null);
            }

            return self::validateYouTubeId($explodedPath[0] ?? null);
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
     * Validates a YouTube video ID against YouTube's character set
     * (A–Z, a–z, 0–9, hyphen, underscore). Returns null for anything else so
     * untrusted input is never interpolated into embed or image URLs.
     */
    private static function validateYouTubeId(?string $id): ?string
    {
        if ($id === null || $id === '') {
            return null;
        }
        return preg_match('/^[A-Za-z0-9_-]+$/', $id) ? $id : null;
    }
    
    /**
     * Gets the video id from a Vimeo URL.
     * Handles both vimeo.com/{id} and player.vimeo.com/video/{id} formats.
     */
    public static function getVimeoIdFromUrl(string $url): ?string
    {
        $parts = parse_url($url);
        $path = $parts['path'] ?? null;

        if (!$path) {
            return null;
        }

        $segments = explode('/', trim($path, '/'));

        // player.vimeo.com paths are /video/{id} — skip the leading 'video' segment
        if (($segments[0] ?? null) === 'video') {
            return $segments[1] ?? null;
        }

        return $segments[0] ?? null;
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

        // vimeo.com/{id}/{hash} — hash is the second segment
        // skip if first segment is 'video' (player URL with no hash in path)
        if (($segments[0] ?? null) === 'video') {
            return null;
        }

        return $segments[1] ?? null;
    }

}