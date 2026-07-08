# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

A Craft CMS 5.x plugin (`viget/craft-video-embed`) that turns a YouTube or Vimeo
watch URL into structured video metadata (embed URL, canonical URL, thumbnail,
type, id). It is pure string parsing — given a URL in, it returns a `VideoData`
object out, or `null` when the URL can't be parsed.

## Design constraint: no external API calls, no caching

**This plugin must never make HTTP/API calls (including oEmbed) and must never
require a caching layer.** Everything is derived synchronously from the URL
string itself. Keep these invariants when adding features:

- No network I/O in `VideoData`, `ParsingHelper`, the service, or anywhere in
  the request path. `VideoData` is a pure, synchronous value object — keep it
  that way.
- No runtime config, credentials, or API tokens.
- No cache dependency. Because nothing is fetched, nothing needs caching.

Consequences to accept rather than "fix" with an API:

- **Vimeo thumbnails are unavailable.** `VideoData::forVimeo()` sets
  `image: null` on purpose — Vimeo's thumbnail URL can't be derived from the ID
  and would require an oEmbed/API lookup. YouTube thumbnails work because their
  URL is derivable from the ID (`i.ytimg.com/vi/{id}/hqdefault.jpg`).
- **Video orientation (portrait/vertical) is unknown for Vimeo** for the same
  reason. Do not add an oEmbed/API call to determine it. If orientation is ever
  modeled, represent "not determined" honestly (e.g. `null`/`unknown`) rather
  than guessing.

If a consumer needs thumbnails or orientation for Vimeo, that fetching + caching
belongs in the consuming application, not in this plugin.

## Commands

```bash
composer install              # install dependencies (incl. phpunit dev dep)
composer phpunit               # run the full unit test suite
vendor/bin/phpunit --colors=auto                          # same, directly
vendor/bin/phpunit --filter testGetVimeoIdFromUrl         # run a single test
vendor/bin/phpunit tests/ParsingHelperTest.php            # run a single file
```

Tests run on every pull request via `.github/workflows/`. `phpunit.xml` sets
`failOnWarning="true"`, so a triggered PHP warning fails the build — parsing
code must not emit warnings on malformed input.

A `.ddev/` config exists for running the plugin inside a local Craft site.

## Architecture

The flow is a single path from URL string to `VideoData`:

```
craft.videoEmbed.getVideoData($url)        Twig entry point
  └─ services/VideoEmbed::getVideoData()    thin service (Craft Component)
       └─ helpers/ParsingHelper             all parsing logic lives here
            └─ models/VideoData             immutable result (forYoutube/forVimeo factories)
```

- **`src/VideoEmbed.php`** — the Craft `Plugin` class. Its only real job is to
  register the `craft.videoEmbed` Twig variable (bound to the service) in
  `init()`. Note `public static VideoEmbed $plugin` is a deprecated back-compat
  reference flagged for removal in v4 — don't build new code on it.

- **`src/services/VideoEmbed.php`** — the public API surface called from Twig.
  `getVideoData()` is the canonical method and delegates straight to
  `ParsingHelper`. `getEmbedUrl()` is **deprecated** (logs via Craft's
  deprecator) and still contains the old standalone parsing logic; prefer
  `getVideoData()->embedUrl`. `isVideoUrl()` reports whether a URL is a
  recognized YouTube/Vimeo URL.

- **`src/helpers/ParsingHelper.php`** — the heart of the plugin. Pure static
  methods, no state. `getVideoTypeFromUrl()` classifies by host (stripping
  `www.`) against the `YOUTUBE_URLS` / `VIMEO_URLS` host allowlists, returning a
  `VideoType`. The per-platform extractors (`getYouTubeIdFromUrl`,
  `getVimeoIdFromUrl`, `getVimeoHashFromUrl`) each handle several URL shapes —
  read the docblocks for the exact formats supported (watch params, youtu.be
  short links, vimeo private-hash URLs, player.vimeo.com embed URLs).

- **`src/models/VideoData.php`** — the immutable result object built via the
  `forYoutube()` / `forVimeo()` static factories, which encode the per-platform
  URL templates. Both return `null` when given no id, which propagates up as the
  "couldn't parse" signal.

- **`src/enums/VideoType.php`** — `YOUTUBE` / `VIMEO` / `UNKNOWN`. `UNKNOWN` is
  the sentinel that makes `getVideoDataFromUrl()` return `null`.

## Conventions

- All parsing must degrade to `null` (or `VideoType::UNKNOWN`), never throw, on
  malformed/unknown URLs — and must not emit PHP warnings (CI fails on warning).
- Add new URL-shape support by extending the `ParsingHelper` extractors and
  covering each shape in `tests/ParsingHelperTest.php`, the single source of
  truth for supported formats.
- New public behavior is exposed through `VideoData` fields, not new service
  methods, where possible — keep the service thin.
