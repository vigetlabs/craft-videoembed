# Releasing Video Embed

This plugin is distributed through [Packagist](https://packagist.org/packages/viget/craft-video-embed)
and the [Craft Plugin Store](https://plugins.craftcms.com/). There is **no build step and no
publish command** — a release is just a Git tag plus an updated changelog. Packagist picks up the
new tag automatically (via the repository webhook), and the Craft Plugin Store reads the
`changelogUrl` declared in `composer.json` to show release notes to users.

## Branch / tag conventions

- **`v3` is the active branch** for the Craft 5 line. All v3 work lands here, and CI
  (`.github/workflows/tests.yml`) runs PHPUnit on every push and pull request to `v3`.
- **Tags for the v3 line omit the `v` prefix** — e.g. `3.0.0`, `3.0.1`. (The older `v1`/`v2`
  lines used a `v` prefix like `v2.0.4`; do not follow that pattern for v3.)
- This project follows [Semantic Versioning](http://semver.org/):
  - **Patch** (`3.0.x`) — bug fixes only.
  - **Minor** (`3.x.0`) — new, backward-compatible features.
  - **Major** (`x.0.0`) — breaking changes / new Craft major version.
- Bump `schemaVersion` **only** when a release ships a migration. A plain feature/bugfix release
  does not change it. As of #38/#43, `schemaVersion` is declared on the plugin class
  (`src/VideoEmbed.php`, the Craft 5 convention), **not** in `composer.json`'s `extra` block.

## Release steps

1. **Make sure `v3` is green.** Pull the latest `v3`, then run the tests:
   ```bash
   git checkout v3
   git pull
   composer phpunit
   ```
   CI must be passing on `v3` before you tag.

2. **Decide the version number** from the unreleased changes (patch vs. minor vs. major, per
   the rules above).

3. **Update `CHANGELOG.md`.** Add a new section at the top, directly under the
   "...Semantic Versioning" line, above the previous release. Use the
   [Keep a Changelog](http://keepachangelog.com/) format with an ISO date and `Added` / `Fixed`
   / `Changed` groups. Reference the relevant issue or PR with a Markdown link. Example:
   ```markdown
   ## 3.1.0 - 2026-06-16
   ### Added
   - Short description [#40](https://github.com/vigetlabs/craft-videoembed/pull/40)

   ### Fixed
   - Short description [#35](https://github.com/vigetlabs/craft-videoembed/issues/35)
   ```
   Only list **user-facing** changes. Internal/CI/tooling changes are omitted (see the list below).

4. **Commit the changelog** to `v3`. Follow the project commit format:
   ```
   [n/a] Prep for 3.1.0 release
   ```
   (Match the style of the `Prep for 3.0.1 release` commit — changelog-only, `[n/a]` ticket tag.)

5. **Tag the release commit and push the tag** (no `v` prefix):
   ```bash
   git tag 3.1.0
   git push origin v3
   git push origin 3.1.0
   ```

6. **Create the GitHub Release.** Draft a release in the GitHub UI (or `gh release create`)
   against the new tag, with the changelog entries as the release notes. This is optional for
   distribution but keeps GitHub's release list in sync with the changelog.

7. **Verify publication.** Within a few minutes the new version should appear on
   [Packagist](https://packagist.org/packages/viget/craft-video-embed). If it does not, open the
   package on Packagist and use **Update** (the webhook occasionally needs a manual nudge). The
   Craft Plugin Store will reflect the new changelog entry once Packagist has the tag.

## Notes

- `changelogUrl` in `composer.json` points at the raw `CHANGELOG.md` on the `v3` branch, so the
  changelog **must** be committed to `v3` for the Plugin Store to display the notes.
- There is no version constant in the plugin source to update — the version comes entirely from
  the Git tag, so do not hand-edit a version number anywhere besides the changelog heading.
