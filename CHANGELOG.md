# Video Embed Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/).

## Unreleased
### Added
- Scheme-less, host-like URLs (e.g. `www.youtube.com/watch?v=…`, `youtu.be/…`, `vimeo.com/…`) are now recognized by `isVideoUrl()`, `getEmbedUrl()`, and `getVideoData()` [#51](https://github.com/vigetlabs/craft-videoembed/issues/51) [#59](https://github.com/vigetlabs/craft-videoembed/pull/59)

### Changed
- `getEmbedUrl()` now returns an absolute `https://` URL instead of a protocol-relative `//…` URL [#42](https://github.com/vigetlabs/craft-videoembed/pull/42)
- `getEmbedUrl()` is no longer deprecated — it is a supported thin wrapper over `getVideoData()->embedUrl` [#42](https://github.com/vigetlabs/craft-videoembed/pull/42)
- `isVideoUrl()` now returns `true` only when an embeddable video can be produced, so it agrees with `getVideoData()`; a host-valid URL with no extractable ID (e.g. `https://youtube.com/`) now returns `false` [#54](https://github.com/vigetlabs/craft-videoembed/issues/54) [#59](https://github.com/vigetlabs/craft-videoembed/pull/59)
- URL matching is now strict: hosts must match exactly (a look-alike such as `notyoutube.com` is rejected) and only `http`/`https` URLs are accepted (protocol-relative and `javascript:`/`ftp:` URLs are rejected) [#42](https://github.com/vigetlabs/craft-videoembed/pull/42)

### Removed
- `getEmbedUrl()` no longer appends the legacy Vimeo Froogaloop query params (`?player_id=video&api=1`) to Vimeo embed URLs. **Upgrade note:** integrations that drove the Vimeo player through those params should migrate to the [Vimeo Player SDK](https://developer.vimeo.com/player/sdk) [#39](https://github.com/vigetlabs/craft-videoembed/issues/39) [#42](https://github.com/vigetlabs/craft-videoembed/pull/42)

### Fixed
- YouTube `/embed/{id}` and `/live/{id}` URLs now resolve the correct video ID, and non-video routes (`/watch`, `/playlist`, …) no longer return a bogus ID [#50](https://github.com/vigetlabs/craft-videoembed/issues/50) [#58](https://github.com/vigetlabs/craft-videoembed/pull/58)
- Stacked host prefixes such as `www.m.youtube.com` are now recognized as YouTube [#52](https://github.com/vigetlabs/craft-videoembed/issues/52) [#58](https://github.com/vigetlabs/craft-videoembed/pull/58)
- Mobile (`m.`) and YouTube Music (`music.`) subdomains resolve to YouTube [#36](https://github.com/vigetlabs/craft-videoembed/issues/36) [#42](https://github.com/vigetlabs/craft-videoembed/pull/42)
- `getYouTubeIdFromUrl()` no longer throws a `TypeError` on array-valued `?v[]=` query params [#34](https://github.com/vigetlabs/craft-videoembed/issues/34) [#42](https://github.com/vigetlabs/craft-videoembed/pull/42)

### Security
- Vimeo private-hash parsing rejects array-valued `?h[]=` params (previously an uncaught `TypeError`) and validates the hash against `[A-Za-z0-9]`, so untrusted characters can't be interpolated into the embed or canonical URL [#49](https://github.com/vigetlabs/craft-videoembed/issues/49) [#58](https://github.com/vigetlabs/craft-videoembed/pull/58)
- YouTube video IDs are validated against `[A-Za-z0-9_-]` before being interpolated into embed and image URLs [#42](https://github.com/vigetlabs/craft-videoembed/pull/42)

## 3.1.0 - 2026-06-16
### Added
- Exposed `isVertical` for YouTube Shorts [#40](https://github.com/vigetlabs/craft-videoembed/pull/40)

### Changed
- YouTube embeds now include `rel=0` so related-video suggestions stay on the source channel [#48](https://github.com/vigetlabs/craft-videoembed/pull/48)

### Fixed
- Fixed Vimeo ID parsing for channel, group, and showcase URLs [#35](https://github.com/vigetlabs/craft-videoembed/issues/35) [#41](https://github.com/vigetlabs/craft-videoembed/pull/41)
- The "plugin loaded" log message now only fires when `devMode` is on, instead of on every production request [#38](https://github.com/vigetlabs/craft-videoembed/issues/38) [#43](https://github.com/vigetlabs/craft-videoembed/pull/43)

## 3.0.1 - 2026-05-08
### Fixed
- Fixed Vimeo URLs containing a hash (private/unlisted video token) not generating a valid embed URL [#23](https://github.com/vigetlabs/craft-videoembed/issues/23)
- Fixed malformed YouTube canonical URL where the `v` query parameter key was missing (`?=ID` → `?v=ID`) [#26](https://github.com/vigetlabs/craft-videoembed/issues/26)

## 3.0.0 - 2025-02-04
### Added
- Updated for Craft 5 [#14](https://github.com/vigetlabs/craft-videoembed/pull/14)

### Fixed
- Fixed PHP Warning with YouTube ID parsing [#18](https://github.com/vigetlabs/craft-videoembed/issues/18)

## 2.0.3 - 2024-04-01
### Added
- Expand getYouTubeIdFromUrl to allow shortened YouTube URLs

## 2.0.0 - 2022-09-07
### Added
- Updated for Craft 4

## 1.2.2 - 2020-02-13
### Added
- Additional checking to prevent errors with bad URLs

## 1.2.1 - 2019-01-18
### Added
- Update Craft version in composer.json

## 1.2.0 - 2017-09-13
### Added
- Updated for Craft 3
