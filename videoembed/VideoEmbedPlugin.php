<?php
namespace Craft;

class VideoEmbedPlugin extends BasePlugin
{
	function getName()
	{
		 return Craft::t('Video Embed');
	}

	function getVersion()
	{
		return '1.1';
	}

	function getDeveloper()
	{
		return 'Trevor Davis';
	}

	function getDeveloperUrl()
	{
		return 'http://viget.com';
	}
}