# Craft Video Embed Plugin

Craft plugin to generate an embed URL from a YouTube or Vimeo URL.

## Installation

1. Copy `videoembed` folder to `craft/plugins`.
2. Navigate to the plugins page in the Craft control panel and install **Video Embed**.

## Usage

Pass a YouTube or Vimeo URL to the `getEmbedUrl` method and an embed URL will be returned.

```
<iframe src="{{ craft.videoEmbed.getEmbedUrl('https://www.youtube.com/watch?v=6xWpo5Dn254') }}"></iframe>
```

**Output:**

```
<iframe src="//www.youtube.com/embed/6xWpo5Dn254"></iframe>
```