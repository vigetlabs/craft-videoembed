# Video Embed Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/).

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
