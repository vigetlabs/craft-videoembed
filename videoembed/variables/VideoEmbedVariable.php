<?php
namespace Craft;

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
		if ($this->_isYoutube($url)) {
			$url_parts = parse_url($url);
			parse_str($url_parts['query'], $segments);

			return '//www.youtube.com/embed/' . $segments['v'];
		} else if ($this->_isShortYoutube($url)) {
			$url_parts = parse_url($url);

			return '//www.youtube.com/embed' . $url_parts['path'];
		} else if ($this->_isVimeo($url)) {
			$url_parts = parse_url($url);
			$segments = explode('/', $url_parts['path']);

			return '//player.vimeo.com/video/' . $segments[1] . '?player_id=video&api=1';
		}
	}


	/**
	 * Determine whether the url is a youtube or vimeo url
	 * @param string $url
	 * @return boolean
	 */
	public function isVideoUrl($url)
	{
		return ($this->_isYoutube($url) || $this->_isVimeo($url));
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
}