# Video Embed Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/).

## 3.0.2 - TBD
### Added
- Support for YouTube Shorts, Live, Embed, and legacy `/v/` URL formats
- Support for Vimeo channel and group URLs (`/channels/slug/ID`, `/groups/slug/videos/ID`)
- `rel=0` added to YouTube embed URLs to restrict related video suggestions to the same channel

### Fixed
- YouTube ID validation now rejects characters outside `[A-Za-z0-9_-]` before use in embed/image URLs
- Host matching switched from `strripos()` substring search to exact match via `parse_url()`
- Non-http/https schemes (javascript:, ftp:, protocol-relative) now return null before host resolution

### Changed
- `getEmbedUrl()` is no longer deprecated. Its old hand-rolled implementation (which used the broken substring host matching) has been replaced with a thin wrapper over `getVideoData()->embedUrl`. Existing callers require no changes.

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
