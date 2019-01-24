<?php
/**
 * Video Embed plugin for Craft CMS 3.x
 *
 * Generate an embed URL from a YouTube or Vimeo URL
 *
 * @link      https://www.viget.com/
 * @copyright Copyright (c) 2017 Trevor Davis
 */

namespace viget\videoembed\variables;

use viget\videoembed\VideoEmbed;

use Craft;

/**
 * @author    Trevor Davis
 * @package   VideoEmbed
 * @since     1.2.0
 */
class VideoEmbedVariable
{
    /**
     * Take a youtube or vimeo url and return the embed url
     *
     * @param string $url
     * @return string
     */
    public function getEmbedUrl($url)
    {
        $url_parts = parse_url($url);

        if ($this->_isYoutube($url) || $this->_isShortYoutube($url)) {
            if ($this->_isYoutube($url)) {
                // Normal YouTube URL
                parse_str($url_parts['query'], $segments);
                $id = $segments['v'];
            } else {
                // Short YouTube URL
                $id = substr($url_parts['path'], 1);
            }
            $options = $this->_generateYouTubeOptions();

            return '//www.youtube.com/embed/' . $id . $options;

        } else if ($this->_isVimeo($url)) {
            $segments = explode('/', $url_parts['path']);
            $id = $segments[1];
            $options = $this->_generateVimeoOptions();

            return '//player.vimeo.com/video/' . $id . $options;
        }
    }

    /**
     * Determine whether the url is a youtube or vimeo url
     * @param string $url
     * @return boolean
     */
    public function isVideoUrl($url)
    {
        return ($this->_isYoutube($url) || $this->_isShortYoutube($url) || $this->_isVimeo($url));
    }

    /**
     * Is the url a youtube url
     * @param string $url
     * @return boolean
     */
    private function _isYoutube($url)
    {
        return strripos($url, 'youtube.com') !== FALSE;
    }

    /**
     * Is the url a youtube short url
     * @param string $url
     * @return boolean
     */
    private function _isShortYoutube($url)
    {
        return strripos($url, 'youtu.be') !== FALSE;
    }


    /**
     * Is the url a vimeo url
     * @param string $url
     * @return boolean
     */
    private function _isVimeo($url)
    {
        return strripos($url, 'vimeo.com') !== FALSE;
    }


    /**
     * Generate YouTube URL parameters
     * @return string
     */
    private function _generateYouTubeOptions()
    {
        $settings = VideoEmbed::getInstance()->getSettings();
        $options = [];

        // Autoplay
        if ($settings->ytAutoplay === 'true') {
            array_push($options, 'autoplay=1');
        }

        // Color
        if ($settings->ytColor === 'white') {
            array_push($options, 'color=white');
        }

        // Controls
        if (!$settings->ytControls) {
            array_push($options, 'controls=0');
        }

        // Enable JS API
        if ($settings->ytEnableJsApi === 'true') {
            array_push($options, 'enablejsapi=1');
        }

        // Fullscreen
        if (!$settings->ytFullscreen) {
            array_push($options, 'fs=0');
        }

        // Loop
        if ($settings->ytLoop === 'true') {
            array_push($options, 'loop=1');
        }

        // Related
        if (!$settings->ytRelated) {
            array_push($options, 'rel=0');
        }

        // Show Info
        if (!$settings->ytShowInfo) {
            array_push($options, 'showinfo=0');
        }

        if ($options) {
            return '?' . join('&', $options);
        } else {
            return '';
        }
    }

    /**
     * Generate Vimeo URL parameters
     * @return string
     */
    private function _generateVimeoOptions()
    {
        $settings = VideoEmbed::getInstance()->getSettings();
        $options = ['player_id=video', 'api=1'];

        // Autoplay
        if ($settings->vAutoplay === 'true') {
            array_push($options, 'autoplay=1');
        }

        // Byline
        if (!$settings->vByline) {
            array_push($options, 'byline=0');
        }

        // Color
        if ($settings->vColor) {
            array_push($options, 'color=' . substr($settings->vColor, 1));
        }

        // Loop
        if ($settings->vLoop === 'true') {
            array_push($options, 'loop=1');
        }

        // Portrait
        if (!$settings->vPortrait) {
            array_push($options, 'portrait=0');
        }

        // Title
        if (!$settings->vTitle) {
            array_push($options, 'title=0');
        }

        if ($options) {
            return '?' . join('&', $options);
        } else {
            return '';
        }
    }
}
