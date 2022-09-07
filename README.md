# Video Embed plugin for Craft CMS 4.x

Generate an embed URL from a YouTube or Vimeo URL.

## Requirements

This plugin requires Craft CMS 4.0.0-beta or later.

## Installation

To install the plugin, follow these instructions.
****
1. Open your terminal and go to your Craft project:

        cd /path/to/project

2. Then tell Composer to load the plugin:

        composer require viget/craft-video-embed

3. In the Control Panel, go to Settings → Plugins and click the “Install” button for Video Embed.

## Using Video Embed

Pass a YouTube or Vimeo URL to the `getEmbedUrl` method and an embed URL will be returned.

```
<iframe src="{{ craft.videoEmbed.getEmbedUrl('https://www.youtube.com/watch?v=6xWpo5Dn254') }}"></iframe>
```

**Output:**

```
<iframe src="//www.youtube.com/embed/6xWpo5Dn254"></iframe>
```

***

<a href="http://code.viget.com">
  <img src="http://code.viget.com/github-banner.png" alt="Code At Viget">
</a>

Visit [code.viget.com](http://code.viget.com) to see more projects from [Viget.](https://viget.com)