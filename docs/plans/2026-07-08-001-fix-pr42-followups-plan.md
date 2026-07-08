---
title: "fix: PR #42 hardening follow-ups (#49–#55)"
type: fix
status: active
created: 2026-07-08
related_issues: [49, 50, 51, 52, 53, 54, 55]
related_pr: 42
execution_posture: characterization-first
---

# fix: PR #42 hardening follow-ups (#49–#55)

## Summary

PR #42 hardened URL parsing in the Video Embed plugin and left seven reviewed
follow-ups (#49–#55). This plan lands them as small, independently reviewable
increments, front-loaded with a **characterization/coverage baseline** that pins
current behavior — including the sharp edges the later fixes will file down — so
every behavioral change lands as an intentional, legible diff against green tests.

The work splits into two phases with a hard gate between them:

- **Phase 1 — Coverage baseline (merge first, standalone).** Purely additive
  tests. No `src/` changes. Green on the current `v3`. This is the safety net;
  it merges before any fix begins.
- **Phase 2 — Fixes and docs (only after the baseline is merged).** Each unit is
  one ticket, and its diff includes flipping the specific baseline assertions it
  changes — so a reviewer sees exactly what behavior moved and why.

The user has explicitly asked to merge Phase 1 before starting Phase 2.

---

## Problem Frame

The plugin exposes URL parsing through `ParsingHelper` (static) and the
`VideoEmbed` service (template-facing via the `craft.videoEmbed` Twig variable).
PR #42 tested `ParsingHelper` well but left gaps and known defects:

- **No service-layer tests.** `getEmbedUrl()`/`isVideoUrl()` behavior is only
  exercised indirectly through `ParsingHelper`; a regression in the delegation
  would pass CI (#53).
- **Defects with no regression net:** the Vimeo hash path throws a `TypeError`
  on `?h[]=` and never validates the hash charset (#49); reserved YouTube path
  segments (`/embed/`, `/live/`, bare `/watch`, `/playlist`) are returned as
  video IDs (#50); stacked host prefixes (`www.m.youtube.com`) are rejected (#52).
- **Contract decisions:** scheme-less URLs are silently no longer recognized
  (#51); `isVideoUrl()` (host-only) and `getVideoData()` (needs an ID) disagree,
  a null-deref risk under Twig `strict_variables` (#54).
- **Release docs** for the 3.1.0 behavior changes, including the approved
  Froogaloop-param removal, are not yet written (#55).

Changing any of this without first pinning the current contract risks silent
regressions in a published plugin consumed by site templates.

---

## Scope Boundaries

### In scope

Tickets #49, #50, #51, #52, #53, #54, #55 — the full PR #42 follow-up set.

### Deferred to Follow-Up Work

- **Broader test-gap tickets #37, #45, #46** (numeric Vimeo slug / Vimeo canonical
  URL assertions; Shorts `?v=`-vs-`isVertical` mismatch; brittle embedUrl
  assertion). The baseline is scoped **narrowly to the surface PR #42 touched**,
  per the confirmed call-out. These remain open and can reuse the baseline's test
  scaffolding later.
- **#38** (declare `schemaVersion` on the plugin class) — unrelated to URL parsing.
- **#39** (evaluate removing Froogaloop params) — the *evaluation* is done and
  approved; its only remaining action is the changelog note folded into #55.

### Non-goals

- No change to the Vimeo ID-extraction structure shipped in #41.
- No new provider support, no new public methods, no signature changes.
- No CI/workflow changes — the existing `phpunit` GitHub check already runs `tests/`.

---

## Key Technical Decisions

- **Characterization-first, additive baseline.** Phase 1 asserts what the code
  does *today* (correct behavior AND current defects), so Phase 2 fixes surface
  as assertion flips. Defect characterizations are labeled with a
  `// Characterizes current behavior — flipped by #NN` comment so no reviewer
  mistakes them for endorsed behavior. This is the standard legacy-code
  characterization technique and directly serves the "easy to verify" goal.
- **Service tests use the plain PHPUnit base, no Craft bootstrap.** The existing
  `tests/ParsingHelperTest.php` extends `PHPUnit\Framework\TestCase` and calls
  statics; `new VideoEmbed()` was verified during review to instantiate without a
  Craft application because `getEmbedUrl`/`isVideoUrl`/`getVideoData` delegate to
  static `ParsingHelper` methods. If instantiation unexpectedly needs a bootstrap,
  the fallback is a minimal `tests/bootstrap.php` — noted in U1 verification.
- **`failOnWarning="true"` is a feature here.** The Vimeo `?h[]=` defect (#49)
  raises a `TypeError` (an exception, not a warning), so it is characterized with
  `expectException(\TypeError::class)` in Phase 1 and asserted to return `null`
  after the fix in Phase 2. Any *new* PHP warning introduced by a fix will fail CI.
- **Call-out defaults (revisit at Phase 2 start, not now):** #51 → normalize and
  accept scheme-less URLs; #54 → tighten `isVideoUrl()` to require an extractable
  ID so it agrees with `getVideoData()`. Baseline-first means these stay open until
  we reach U6/U7; the baseline merely documents the current (pre-decision) behavior.
- **Sequencing puts contract-shifting fixes last.** Independent parser fixes
  (#49/#50/#52) land before the scheme-normalization (#51), which lands before the
  `isVideoUrl` alignment (#54), because #54's correct target depends on the final
  parser + scheme behavior. Docs (#55) are last and land post-merge.

---

## Execution Posture

**Characterization-first.** Phase 1 units carry an execution note to write the
pinning tests before any `src/` change exists. Phase 2 units each begin by
flipping the relevant baseline assertions (the failing-test step) and then make
them pass — the diff *is* the review artifact.

---

## Implementation Units

### Phase 1 — Coverage baseline (merge first)

#### U1. Service-layer characterization tests

**Goal:** Pin the current behavior of the `VideoEmbed` service so its delegation
to `ParsingHelper` has a regression net (#53), and document the current
`isVideoUrl`/`getVideoData` disagreement (#54) as characterization.

**Requirements:** #53 (primary), #54 (characterization only).

**Dependencies:** none.

**Files:**
- `tests/VideoEmbedTest.php` (new)

**Approach:** New test class extending `PHPUnit\Framework\TestCase`, instantiating
`new viget\videoembed\services\VideoEmbed()` once per test (or in `setUp`). Assert
at the service boundary only — this is about proving the delegation, not
re-testing `ParsingHelper` internals.

**Execution note:** Purely additive; must be green on current `v3` with no `src/`
change.

**Patterns to follow:** `tests/ParsingHelperTest.php` structure, naming, and
docblock style.

**Test scenarios:**
- Happy path: `getEmbedUrl('https://www.youtube.com/watch?v=dQw4w9WgXcQ')` ===
  `'https://www.youtube.com/embed/dQw4w9WgXcQ?rel=0'` (post-#48 `rel=0`).
- Happy path: `getEmbedUrl('https://vimeo.com/9999999999')` ===
  `'https://player.vimeo.com/video/9999999999'`.
- Error path: `getEmbedUrl('https://notavideo.com/x')` === `null`.
- Happy path: `isVideoUrl('https://www.youtube.com/watch?v=dQw4w9WgXcQ')` ===
  `true`; `isVideoUrl('https://vimeo.com/9999999999')` === `true`.
- Exact-host delegation: `isVideoUrl('https://notyoutube.com/watch?v=ID')` ===
  `false` (proves the service reflects the hardened host match, not a substring).
- **Characterizes #54 (flipped by U7):** `isVideoUrl('https://youtube.com/')`
  === `true` while `getVideoData('https://youtube.com/')` === `null`. Comment:
  documents the host-only vs ID-required mismatch.
- Delegation identity: `getEmbedUrl($url)` equals
  `getVideoData($url)?->embedUrl` for a representative YouTube and Vimeo URL.

**Verification:** `composer phpunit` green; new file adds tests, `src/` untouched.
If `new VideoEmbed()` throws for lack of a Craft app, add a minimal
`tests/bootstrap.php` (documented in the PR) and point phpunit at it — do not
change `src/` to make the test instantiable.

---

#### U2. ParsingHelper guard + defect characterization tests

**Goal:** Reach the new `parse_url()` false-guard branches (#53) and pin the
current behavior of the three parser defects (#49, #50, #52) and the scheme-less
rejection (#51) so their Phase 2 fixes are legible assertion flips.

**Requirements:** #53 (primary); #49/#50/#51/#52 (characterization only).

**Dependencies:** none (independent of U1).

**Files:**
- `tests/ParsingHelperTest.php` (extend)

**Approach:** Add tests to the existing class. Each defect characterization gets a
`// Characterizes current behavior — flipped by #NN` comment so intent is
unambiguous in review.

**Execution note:** Additive; green on current `v3`.

**Patterns to follow:** existing section-comment banners and per-assertion
messages in `tests/ParsingHelperTest.php`.

**Test scenarios:**
- **#53 guards:** `getVideoTypeFromUrl('http://')` === `VideoType::UNKNOWN` and
  `getYouTubeIdFromUrl('http://')` === `null` (`http://` makes `parse_url` return
  `false`, reaching the `!is_array(...)` branches).
- **Characterizes #49 (flipped by U3):** `getVimeoHashFromUrl('https://player.vimeo.com/video/123?h[]=abc')`
  throws `\TypeError` (`expectException`); and
  `getVimeoHashFromUrl('https://vimeo.com/12345/abc"onload')` returns the raw
  unvalidated `'abc"onload'`.
- **Characterizes #50 (flipped by U4):** `getYouTubeIdFromUrl('https://www.youtube.com/embed/dQw4w9WgXcQ')`
  === `'embed'`; `/live/dQw4w9WgXcQ` === `'live'`; bare `/watch` === `'watch'`;
  `/playlist?list=PL1` === `'playlist'`.
- **Characterizes #52 (flipped by U5):** `getVideoTypeFromUrl('https://www.m.youtube.com/watch?v=abc')`
  === `VideoType::UNKNOWN`.
- **Characterizes #51 (flipped by U6):** `getVideoTypeFromUrl('www.youtube.com/watch?v=abc')`
  === `VideoType::UNKNOWN` (scheme-less, no host parsed).

**Verification:** `composer phpunit` green; only `tests/` changed. Because each
defect test asserts today's behavior, the suite passes without touching `src/`.

---

### Phase 2 — Fixes and docs (only after Phase 1 is merged)

> Gate: do not begin until the Phase 1 baseline PR is merged to `v3`.

#### U3. Fix Vimeo hash TypeError + charset validation (#49)

**Goal:** Reject array-valued `?h[]=` params and validate the hash charset,
mirroring the YouTube-side `validateYouTubeId()` treatment.

**Requirements:** #49.

**Dependencies:** U2 (flips its #49 characterizations).

**Files:**
- `src/helpers/ParsingHelper.php`
- `tests/ParsingHelperTest.php`

**Approach:** Add a private `validateVimeoHash(?string): ?string` mirroring
`validateYouTubeId()`; add an `is_string()` guard on the query value before
returning; route both the `?h=` query branch and the `vimeo.com/{id}/{hash}` path
branch through the validator. Assumption: Vimeo unlisted hashes are alphanumeric —
if hyphen/underscore occurs in real tokens, widen the allowlist.

**Patterns to follow:** `validateYouTubeId()` in the same file.

**Test scenarios:**
- Flip U2's #49 tests: `?h[]=abc` now returns `null` (no `TypeError`);
  `vimeo.com/12345/abc"onload` returns `null`.
- Valid hash preserved: `player.vimeo.com/video/123?h=abcdef1234` returns the hash.
- End-to-end: `getVideoData('https://vimeo.com/123/abcdef1234')->embedUrl` contains
  only the safe hash charset.

**Verification:** `composer phpunit` green; the flipped assertions are the diff.

---

#### U4. Fix reserved YouTube path segments (#50)

**Goal:** Stop returning `embed`/`live`/`watch`/`playlist` as video IDs.

**Requirements:** #50.

**Dependencies:** U2 (flips its #50 characterizations).

**Files:**
- `src/helpers/ParsingHelper.php`
- `tests/ParsingHelperTest.php`

**Approach:** In `getYouTubeIdFromUrl()`, treat `embed` and `live` like `shorts`
(real ID is the second segment); return `null` when the first path segment is a
known non-ID route (`watch`, `playlist`, `feed`, `channel`, `results`, `c`,
`user`, …) with no usable ID.

**Patterns to follow:** the existing `shorts` prefix handling in the same method.

**Test scenarios:**
- Flip U2's #50 tests: `/embed/REALID` and `/live/REALID` return `'REALID'`; bare
  `/watch`, `/playlist` return `null`.
- Regression guard: `watch?v=REALID`, `youtu.be/REALID`, `/shorts/REALID` still
  resolve correctly.

**Verification:** `composer phpunit` green.

---

#### U5. Recognize stacked host prefixes (#52)

**Goal:** Accept `www.m.youtube.com`-style stacked prefixes.

**Requirements:** #52.

**Dependencies:** U2 (flips its #52 characterization).

**Files:**
- `src/helpers/ParsingHelper.php`
- `tests/ParsingHelperTest.php`

**Approach:** Change the single-strip `preg_replace('/^(www|m|music)\./', …)` to
strip stacked leading prefixes in one pass (e.g. `'/^(www\.|m\.|music\.)+/'`).
Keep the anchored-prefix approach — no substring matching.

**Patterns to follow:** the existing prefix strip in `getVideoTypeFromUrl()`.

**Test scenarios:**
- Flip U2's #52 test: `www.m.youtube.com/watch?v=abc` === `YOUTUBE`.
- Fail-safe preserved: `music.youtube.com.evil.com` === `UNKNOWN`;
  `notyoutube.com` === `UNKNOWN`.

**Verification:** `composer phpunit` green.

---

#### U6. Normalize scheme-less URLs (#51)

**Goal:** Accept scheme-less, host-like URLs (`www.youtube.com/...`) end-to-end.

**Requirements:** #51.

**Dependencies:** U2 (flips its #51 characterization). Confirm the normalize-vs-
document decision at Phase 2 start (default: normalize).

**Files:**
- `src/helpers/ParsingHelper.php`
- `tests/ParsingHelperTest.php`

**Approach:** In `getVideoTypeFromUrl()`, when `parse_url()` yields a host-like
string with no scheme, prepend `https://` before parsing. `javascript:`/`ftp:`/
`data:` carry a scheme and still fall through the http/https-only guard, so this
does not weaken the scheme check. Apply the same normalization on the paths
`getYouTubeIdFromUrl`/`getVimeoIdFromUrl` consume so acceptance is consistent
across `isVideoUrl` and `getVideoData`.

**Test scenarios:**
- Flip U2's #51 test: `www.youtube.com/watch?v=abc` === `YOUTUBE`; and
  `getVideoData('www.youtube.com/watch?v=abc')` is non-null with the right ID.
- Scheme guard intact: `javascript://youtube.com/watch?v=ID` === `UNKNOWN`;
  `//youtube.com/...` (protocol-relative) still === `UNKNOWN`.

**Verification:** `composer phpunit` green; if the team instead chooses
document-only, this unit collapses into #55's changelog note and U2's #51
assertion stays as the pinned behavior.

---

#### U7. Align isVideoUrl() with getVideoData() (#54)

**Goal:** Make `isVideoUrl()` return `true` only when an embeddable video can be
produced, eliminating the null-deref surface.

**Requirements:** #54.

**Dependencies:** U2/U1 (flips their #54 characterizations); U4 and U6 (final
parser + scheme behavior determines the correct target).

**Files:**
- `src/services/VideoEmbed.php`
- `src/helpers/ParsingHelper.php` (if a shared predicate is cleaner)
- `tests/VideoEmbedTest.php`, `tests/ParsingHelperTest.php`

**Approach:** Redefine `isVideoUrl()` as `getVideoDataFromUrl($url) !== null` so a
`true` result guarantees usable data. Assumption: `isVideoUrl()` is a "can I embed
this?" guard; if it is instead meant as a cheap host-only sniff, keep host-only and
document that callers must null-check `getVideoData()` — decide at Phase 2 start.

**Test scenarios:**
- Flip U1/U2's #54 characterization: `isVideoUrl('https://youtube.com/')` ===
  `false` (agrees with `getVideoData()` returning `null`).
- Positive cases unchanged: a full `watch?v=` / `vimeo.com/{id}` URL still ===
  `true`.

**Verification:** `composer phpunit` green.

---

#### U8. Release docs for 3.1.0 (#55)

**Goal:** Document the consumer-facing 3.1.0 behavior changes and fix the stale
README example.

**Requirements:** #55.

**Dependencies:** all Phase 2 fixes merged (so the documented behavior is final).

**Files:**
- `CHANGELOG.md`
- `README.md`

**Approach:** Under the 3.1.0 release entry (batched per repo convention, e.g.
commit 1b66fc9), document: `getEmbedUrl()` output is now absolute `https://`;
Vimeo Froogaloop params (`?player_id=video&api=1`) removed (approved — add an
upgrade note pointing integrations to the Vimeo Player SDK, since they break
silently); `getEmbedUrl()` un-deprecated; and the scheme-less input decision from
U6. Fix the README "Output" example, which still shows a protocol-relative iframe
`src` (`//www.youtube.com/embed/…`) instead of the emitted `https://…?rel=0`.

**Test scenarios:** Test expectation: none — documentation only.

**Verification:** README example matches actual `VideoData::forYoutube()` output;
CHANGELOG covers all four deltas plus the Froogaloop upgrade note.

---

## Sequencing

```
Phase 1 (parallel, no deps) ─┬─ U1  service characterization
                             └─ U2  parser guards + defect characterization
        │  (merge Phase 1 baseline PR)  ← HARD GATE
        ▼
Phase 2  U3 (#49) ─┐
         U4 (#50) ─┤ independent parser fixes (any order)
         U5 (#52) ─┘
              │
              ▼
         U6 (#51 normalize)
              │
              ▼
         U7 (#54 align isVideoUrl)   ← depends on U4 + U6
              │
              ▼
         U8 (#55 docs)               ← after all fixes final
```

U1 and U2 can be authored in parallel and ship in one baseline PR. U3/U4/U5 are
mutually independent. U6 precedes U7 because #54's correct target depends on the
final scheme behavior.

---

## Verification Strategy

- **Per unit:** `composer phpunit` (the repo's `phpunit` script; CI runs the same
  `unit` suite over `tests/`). Every unit is green before it lands.
- **Phase 1 gate:** the baseline PR touches only `tests/` and is green — a reviewer
  confirms zero `src/` diff, so it is provably behavior-preserving.
- **Phase 2 legibility:** each fix PR's diff includes flipping the specific baseline
  assertions it changes, so the reviewer reads the behavior delta directly rather
  than reconstructing it. `failOnWarning="true"` catches any new PHP warning.
- **Manual spot-check (U8):** render a YouTube and Vimeo embed in a Twig template to
  confirm the `https://` iframe `src` matches the documented output.

---

## Risks

- **`new VideoEmbed()` may need a Craft bootstrap.** Mitigation in U1: fall back to
  a minimal `tests/bootstrap.php` rather than altering `src/`. Low likelihood — the
  service only delegates to static helpers.
- **#51 normalization scope creep.** Prepending `https://` must not loosen the
  scheme guard; U6 tests pin that `javascript:`/protocol-relative stay rejected.
- **#54 is a public-method behavior change.** Tightening `isVideoUrl()` could
  surprise a caller relying on host-only truthiness; the decision is explicitly
  revisited at Phase 2 start and documented in #55.
- **Base drift already resolved.** This plan targets merged `v3` (post-#41/#48);
  the stale-Vimeo-function confusion from the PR branch does not apply here.

---

## Deferred Questions (resolve at Phase 2 start, not now)

- #51: normalize scheme-less URLs (default) vs document the break only.
- #54: tighten `isVideoUrl()` to require an ID (default) vs document host-only
  semantics and require caller null-checks.

Neither blocks Phase 1 — the baseline documents current behavior either way.
