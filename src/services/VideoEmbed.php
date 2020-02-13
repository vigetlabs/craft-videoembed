<?php

namespace viget\videoembed\services;

use Craft;

class VideoEmbed
{
    /**
     * Take a youtube or vimeo url and return the embed url
     *
     * @param string $url
     * @return string
     */
    public function getEmbedUrl(string $url): ?string
    {
        if ($this->_isYoutube($url)) {
            $urlParts = parse_url($url);
            $query = $urlParts['query'] ?? null;

            if ($query === null) return null;

            parse_str($query, $segments);
            $v = $segments['v'] ?? null;

            if ($v === null) return null;

            return '//www.youtube.com/embed/' . $v;
        } else if ($this->_isShortYoutube($url)) {
            $urlParts = parse_url($url);
            $path = $urlParts['path'] ?? null;

            if ($path === null) return null;

            return '//www.youtube.com/embed' . $path;
        } else if ($this->_isVimeo($url)) {
            $urlParts = parse_url($url);
            $path = $urlParts['path'] ?? null;

            if ($path === null) return null;

            $segments = explode('/', $path);
            $firstSegment = $segments[1] ?? null;

            if ($firstSegment === null) return null;
            
            return '//player.vimeo.com/video/' . $firstSegment . '?player_id=video&api=1';
        } else {
            return null;
        }
    }

    /**
     * Determine whether the url is a youtube or vimeo url
     * @param string $url
     * @return boolean
     */
    public function isVideoUrl(string $url): bool
    {
        return ($this->_isYoutube($url) || $this->_isShortYoutube($url) || $this->_isVimeo($url));
    }

    /**
     * Is the url a youtube url
     * @param string $url
     * @return boolean
     */
    private function _isYoutube(string $url): bool
    {
        return strripos($url, 'youtube.com') !== FALSE;
    }

    /**
     * Is the url a youtube short url
     * @param string $url
     * @return boolean
     */
    private function _isShortYoutube(string $url): bool
    {
        return strripos($url, 'youtu.be') !== FALSE;
    }


    /**
     * Is the url a vimeo url
     * @param string $url
     * @return boolean
     */
    private function _isVimeo($url): bool
    {
        return strripos($url, 'vimeo.com') !== FALSE;
    }
}