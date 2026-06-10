# Video Embed plugin for Craft CMS 5.x

Generate an embed URL from a YouTube or Vimeo URL.

## Design philosophy

This plugin is intentionally **offline**: it derives everything it returns from
the URL string alone. It makes **no external API or oEmbed calls** and requires
**no caching layer**, so it adds no network latency, credentials, or runtime
configuration to your site.

A practical consequence is that some metadata simply isn't available without a
remote lookup, and the plugin returns `null` rather than fetching it:

- **Vimeo thumbnails** (`image`) — a Vimeo thumbnail URL can't be derived from
  the video ID, so `image` is `null` for Vimeo. YouTube thumbnails work because
  their URL is derivable from the ID.
- **Video orientation** (portrait vs. landscape) for Vimeo, for the same reason.

If you need that data, fetch (and cache) it in your application; that
responsibility lives outside this plugin by design.

## Requirements

This plugin requires Craft CMS 5.0.0 or later.

## Installation

To install the plugin, follow these instructions.

1. Open your terminal and go to your Craft project:

        cd /path/to/project

2. Then tell Composer to load the plugin:

        composer require viget/craft-video-embed

3. In the Control Panel, go to Settings → Plugins and click the “Install” button for Video Embed.

## Using Video Embed

Pass a YouTube or Vimeo URL to the `getVideoData` method and a `VideoData` object is returned.

If the plugin is unable to parse the URL, `null` is returned.

- `type` - If the video is `youtube` or `vimeo`
- `id` - The ID of the video
- `image` - The thumbnail of the video (YouTube only; `null` for Vimeo — see [Design philosophy](#design-philosophy))
- `embedUrl` - The URL you would use for the embed
- `url` - The link to the embedded video

**Example:**

```twig
{% set video = craft.videoEmbed.getVideoData('https://www.youtube.com/watch?v=6xWpo5Dn254') %}

{% if video %}
   <iframe src="{{ video.embedUrl }}"></iframe>
{% endif %}
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