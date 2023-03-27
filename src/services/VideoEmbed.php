<?php

namespace viget\videoembed\services;

use Craft;
use craft\errors\DeprecationException;
use viget\videoembed\helpers\ParsingHelper;
use viget\videoembed\models\VideoData;

class VideoEmbed
{

    /**
     * Takes a YouTube or Vimeo URL and returns metadata for the video
     */
    public function getVideoData(string $url): ?VideoData
    {
        return ParsingHelper::getVideoDataFromUrl($url);
    }
    
    /**
     * Take a YouTube or Vimeo url and return the embed url
     *
     * @param string $url
     * @return string|null
     * @throws DeprecationException
     * @deprecated v2.0.2
     * @see self::getVideoData()
     */
    public function getEmbedUrl(string $url): ?string
    {
        Craft::$app->getDeprecator()->log(__METHOD__, 'use getVideoData() instead');
        
        if ($this->_isYoutube($url)) {
            $urlParts = parse_url($url);
            $query = $urlParts['query'] ?? null;

            if ($query === null) {
                return null;
            }

            parse_str($query, $segments);
            $v = $segments['v'] ?? null;

            if ($v === null) {
                return null;
            }

            return '//www.youtube.com/embed/' . $v;
        }

        if ($this->_isShortYoutube($url)) {
            $urlParts = parse_url($url);
            $path = $urlParts['path'] ?? null;

            if ($path === null) return null;

            return '//www.youtube.com/embed' . $path;
        }

        if ($this->_isVimeo($url)) {
            $urlParts = parse_url($url);
            $path = $urlParts['path'] ?? null;

            if ($path === null) {
                return null;
            }

            $segments = explode('/', $path);
            $firstSegment = $segments[1] ?? null;

            if ($firstSegment === null) return null;

            return '//player.vimeo.com/video/' . $firstSegment . '?player_id=video&api=1';
        }

        return null;
    }

    /**
     * Determine whether the url is a YouTube or Vimeo url
     * @param string $url
     * @return boolean
     */
    public function isVideoUrl(string $url): bool
    {
        return ($this->_isYoutube($url) || $this->_isShortYoutube($url) || $this->_isVimeo($url));
    }

    /**
     * Is the url a YouTube url
     * @param string $url
     * @return boolean
     */
    private function _isYoutube(string $url): bool
    {
        return strripos($url, 'youtube.com') !== FALSE;
    }

    /**
     * Is the url a YouTube short url
     * @param string $url
     * @return boolean
     */
    private function _isShortYoutube(string $url): bool
    {
        return strripos($url, 'youtu.be') !== FALSE;
    }


    /**
     * Is the url a Vimeo url
     * @param string $url
     * @return boolean
     */
    private function _isVimeo($url): bool
    {
        return strripos($url, 'vimeo.com') !== FALSE;
    }
}