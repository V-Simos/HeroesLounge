# Heroes Lounge UI Rework — Phase 2 Wave 1 Implementation Plan (Public Competitive Viewing)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.
>
> **Execution tracker:** `docs/superpowers/PROGRESS.md` is the single source of truth for status (the inline `- [ ]` boxes here are NOT maintained — update the tracker at every task/review transition). **Wave 2 (static content: rules, guides, legal, hall of fame, Division-S, FAQ) is a SEPARATE later plan** — this document is Wave 0 (bracket spike) + Wave 1 (public viewing) only.

**Goal:** Extend the `heroeslounge-next` theme (built in Phase 1) to cover the public, read-only competitive-viewing surface: season overview, division pages, playoff brackets, match detail, calendar, team pages, and the season archive. Each page replicates its old theme's components, `[session]` config, data bindings, and frozen URL — only the Twig markup, CSS, and presentation JS change.

**Architecture:** New theme only, alongside the untouched `HeroesLounge-Theme`. All page data comes from existing `rikki.*` (+ `loungestatistics`) components, restyled via the Phase-1 `{% do Component.onRender() %}` data pattern **or** the component-partial-override-by-alias pattern (`partials/<Alias>/default.htm`). Plugins are FROZEN.

**Tech Stack:** October CMS v1 themes (INI front-matter + Twig), hand-rolled CSS with custom properties (no build step), vanilla JS (`lounge.js`), self-hosted fonts, inline lucide SVGs.

**Authoritative references (committed in this repo):**
- Phase 2 spec (binding scope + constraints): `docs/superpowers/specs/2026-07-04-phase-2-public-reskin-design.md`
- Phase 1 spec: `docs/superpowers/specs/2026-07-03-ui-ux-rework-design.md`
- Phase 1 plan (format/voice template this plan matches): `docs/superpowers/plans/2026-07-03-ui-rework-phase-1.md`
- Execution tracker + hard-won gotchas: `docs/superpowers/PROGRESS.md`
- Visual North Star / v2 tokens: `docs/superpowers/specs/assets/dashboard-v2.html` (authoritative) + `reference-design.html`. **No season/division/match/bracket mockups exist** — these pages reuse the established design system in its spirit, with component data bound in.

---

## Crash course / conventions (read once)

This wave carries every Phase-1 convention forward. The load-bearing rules:

- **Binding constraint — pure frontend re-skin.** For every page: replicate the old theme's `[Component]` front-matter, `[session]` access config, data bindings, and **frozen URL** — changing only Twig **markup**, **CSS**, and **presentation JS**. **Nothing** under `plugins/` (rikki.*, rainlab.*, loungestatistics, the `Indikator.Content` dev shim) and **nothing** under `themes/HeroesLounge-Theme/` is ever modified. Reading/copying from the old theme + plugin partials is allowed. **Plugin bugs are replicated as-is, never fixed** (e.g. the `TimelineEntries` season-branch `$allTimelines` bug — we only use the correct `type='division'`/`type='team'` branches; the `DivisionTable` negative-`startIndex` bug is clamped in our override but not "fixed" upstream). No migrations, no access-control changes, no new endpoints.

- **The onRender pattern (Phase-1 established, verified against `Controller.php:1213` on this pinned October v1).** Several components populate their public page-vars in `onRender()` ONLY (empty/absent `onRun`). Rendering them via `{% component %}` would emit the plugin's old Bootstrap partial, so the partial that owns the data attaches the component in front-matter, then calls `{% do Component.setProperty('id', …) %}` (runtime ids) and `{% do Component.onRender() %}`, then reads the public property. **onRender-only components in this wave:** `RecentResults`, `UpcomingMatches`, `ViewMatch`, `DivisionTable`, `RoundMatches`, `TimelineEntries`, `SpoilersToggle` (also `GameStatistics`). Eager-load lazy relations after `onRender()` via `.matches.load(...)` where the component under-loads (see the N+1 notes per task). **Exceptions that need NO `{% do onRender() %}`:** (a) `SeasonOverview` and `DivisionOverview` populate in `init()` (runs every request) — read `SeasonOverview.season` / `DivisionOverview.div` directly; (b) any sub-component invoked via `{% component 'alias' %}` inside an override partial fires its own `onRender()` at render time (this is how `ViewTeam` / `PlayoffOverview` / `participationOverview` drive their children).

- **Component-partial override by alias (case-sensitive!).** A component's plugin `default.htm` is re-skinned by placing a theme partial at `partials/<Alias>/default.htm`, matching the **registered alias casing EXACTLY** (the container FS is case-sensitive on prod Linux; a wrong-case dir is *silently ignored* and the plugin Bootstrap partial renders instead). Prove any new override took effect with a temporary marker string before styling. Existing proof: `partials/DivisionTable/default.htm`. **Casing trap flagged per task:** `SeasonOverview`, `participationOverview` (runtime-added alias — verify it resolves), `PlayoffOverview` (+ `divisionView`), `ViewTeam` and its camelCase children `recentResults`/`divisionTable`/`upcomingMatches`/`roundMatches`/`timeLine`/`teamStatistics` — note `divisionTable` (lowercase) ≠ the existing `DivisionTable` folder, so ViewTeam needs its own lowercase override or it falls back to Bootstrap.

- **DivisionTable override reuse.** `partials/DivisionTable/default.htm` emits header + rows only (consumer supplies the `.table`/`.p` wrapper + `table-foot`). Props: `id`, `teamId`, `surroundingEntries` (default 4; `maxEntries` is a DEAD property — never use it). **5-row cap:** with no `teamId`, `length` is null → the override slices to 5 rows. The **division page needs the FULL table** — do NOT reuse the override there; iterate ALL `DivisionTable.teams` in a division-specific partial. **`showScore` (playoff group-stage) mode is NOT ported** — Task 4 extends the override (or adds a variant) for it. Don't "fix" the shared override; the homepage/dashboard depend on its top-5/window behavior.

- **Match-card reuse.** Task 1 builds `partials/match/card.htm` — the canonical param-driven `.match` card, extracted from the Phase-1 `home/results.htm` `.match` markup (which stays byte-identical; refactoring the home/dashboard consumers onto the shared card is an OPTIONAL later cleanup, not part of this wave). Division round-matches, calendar, match/view header, and team/view match history consume it. **The playoff bracket nodes do NOT use it** — they need a fixed `13rem × 3.875rem` box locked to the frozen geometry (Task 4).

- **Literal frozen-URL rule.** `| page` resolves against the ACTIVE theme. To keep the shared match-card and all cross-links build-order-independent and consistent with the existing Phase-1 partials (which already emit literals like `/match/view/{{ match.id }}`, `/team/view/{{ team.slug }}`, `/{{ season.slug }}`), **use literal frozen paths throughout Wave 1** — matching each old page's `url =`. Switching to `| page` is a deferred cleanup. Literals are MANDATORY for any link into a deferred (Phase-3) or dropped page. Frozen paths (verified against old theme `url =`):

  | Target | Frozen URL | Emit as | Ported |
  |---|---|---|---|
  | Season overview | `/:slug` | `/{{ season.slug }}` | W1 T2 |
  | Division | `/:slug/:divslug` | `/{{ season.slug }}/{{ division.slug }}` | W1 T3 |
  | Season playoff | `/:season-slug/playoff/:playoff-title` | `/{{ season.slug }}/playoff/{{ playoff.title\|url_encode }}` | W1 T4 |
  | Standalone playoff | `/tournament/:playoff-title` | `/tournament/{{ playoff.slug }}` | W1 T4 |
  | Match detail | `/match/view/:id` | `/match/view/{{ match.id }}` | W1 T5 |
  | Calendar | `/calendar` | `/calendar` | W1 T6 |
  | Team page | `/team/view/:slug` | `/team/view/{{ team.slug }}` | W1 T7 |
  | Season archive | `/season/archive` | `/season/archive` | W1 T8 |
  | User/sloth profile | `/user/view/:id` | `/user/view/{{ sloth.id }}` | **DEFERRED P3 — always literal** |
  | Team create | `/team/create` | `/team/create` | **DEFERRED P3 — literal** |
  | Reschedule / manage match | `/team/match/:slug` | `/team/match/{{ team.slug }}` | **DEFERRED P3 — literal** |

  Note the two playoff routes differ: the **in-season** page resolves by playoff **title** (`?season-slug` present), the **standalone** page by playoff **slug** — build each link accordingly. `playoff.title` can contain spaces → `|url_encode`.

- **CSS placement (invariant).** Component-level classes → `assets/css/components.css`, added **BEFORE** the two end-blocks (the `/* focus affordance on clipped elements */` selector list at ~ln 424 and the `@media (prefers-reduced-motion: reduce)` block that must stay LAST). Page-level layout → `assets/css/pages.css`. **Any new focusable element carrying `.chamfer` or its own `clip-path` MUST be added to the focus-affordance selector list** (`outline-offset: -4px`), or its keyboard ring is clipped invisible (WCAG 2.4.7). **Any new `lounge.js`/CSS behavior that animates MUST be neutralized in the reduced-motion block** (e.g. the spoiler-reveal transition, any bracket motion).

- **Spoiler mechanism convention.** The old theme's `SpoilersToggle` component (cookie `showSpoilers`, 30-day, via AJAX `onSetShowSpoilers`) calls a page-defined JS callback. The old callbacks toggled `.spoiler`/`.notext` on `span.f100`/`td.score`/`div.result`. **CRITICAL:** the frozen `heroeslounge.css` (auto-injected by these components' `onRun`/`init`, and loaded LAST via `{% styles %}` so it WINS on shared selectors) defines `.spoiler{background:gray;color:gray}` and `.notext{color:transparent}`. **Never reuse the class names `.spoiler`/`.notext`/`.match.format-mini`/`.result` raw in themed markup** — the legacy rules clobber them. Use a fresh mechanism: a `body`/wrapper class (e.g. `body.reveal-spoilers`) plus a themed `.score-masked` class that blurs scores by default and reveals when the wrapper class is present; wire a new callback (`hlToggleSpoilers(show)`) that toggles the wrapper class. `SpoilersToggle`'s inline script calls your callback on ready with the cookie value, so initial state is correct. Neutralize any blur *transition* under reduced-motion.

- **Verification is live, on fixtures.** No theme PHP → no unit tests. Every task's Verify step is a live check against the running October instance (Docker, `http://localhost:8090`, fixture DB; resume steps in PROGRESS.md § "Resuming a session"). A task is not done while its page 500s, logs Twig errors in `storage/logs`, renders unstyled, shows a blank where an empty state belongs, or emits `href=""`. **The real team DB dump is still pending** (Phase-1 blocker) — fixture verification only; when it lands, all Phase-1/2 pages get a real-data re-verification pass (fixture-blind items tracked in PROGRESS.md). **Parked open item:** ARAM league (`/aram-league-ruleset`, `/guides/aram-signup-guide`) — user to confirm still-running; until then it stays on the old theme via literal URL, zero work here.

- **Commit convention.** One commit per bite-sized, independently-committable step group. Message: `feat(theme-next): <desc>`, then a blank line, then the trailer:

  ```
  feat(theme-next): <desc>

  Co-Authored-By: Claude Fable 5 <noreply@anthropic.com>
  ```

- **Layout mapping.** The old public pages used `plain` / `plain-with-sidebar` / `plain-fluid` layouts (all carry `[session] security="all"` + `[Navigation]` + `[SetTimezone]` + `[staticPage]`). The new `layouts/default.htm` provides the exact equivalent chrome — **map every Wave-1 page to `layout = "default"`**. Do NOT invent a page-level `[session]` block (it lives on the layout, same as old). `SetTimezone` on the layout feeds `UpcomingMatches`/`ViewMatch` timezone vars — do not remove it.

---

## Task 0 — Bracket-render spike (throwaway / de-risk)

Wave-0 from the spec: prove the playoff bracket render **before** committing Wave 1, because it is the highest-uncertainty item. **Deliverable = a PROVEN rendering approach captured as a spike note (below) + committed, NOT a shipped page.** The scratch page is disposable and is reverted at the end of this task; **Task 4 productionizes the chosen approach.**

**Files:**
- Create (throwaway, reverted at end of task): `themes/heroeslounge-next/pages/_spike-bracket.htm`
- Modify (spike note only — keep): this plan file's "SPIKE FINDINGS" block below, filled in + committed.

**Frozen geometry (from `PlayoffOverview::init()`, packet: playoff) — the spike must handle these:**
- Node box LOCKED at `match_width = 13rem` × `match_height = 3.875rem`; `round_width = 15rem`; `width_between_matches = 2rem`; `1rem = 1000` SVG units. Node placement is 100% inline `left`/`top` in rem from `offset_left`/`offset_top`. Connectors are precomputed `polylines` (points in `rem*1000`). **You may restyle the interior of the box only; never change its outer w/h or the connectors desync.**
- `playoff.type` → `total_width × total_height` (rem): `playoffv1` 105×43.9375; `se16` 60×43.9375; `se8`/`playoffv2`/`playoffv4` 45×43.9375; `se32` 75×78; `se64` 90×153; `de4` 61×30; `de8` 76×35; `de16` 106×60; `de8short` 60×45; `playoffv3`/`de6` 60×43.9375; `DivSv1` 75×25; default 105×43.9375. `se64` is 153rem tall → the `#double_elim` wrapper must be BOTH horizontally and vertically scrollable (`overflow:auto`).
- Bracket class hooks: `bracket-winners` / `bracket-losers` / `bracket-finals` (from Cantor `getBracketName` 1/2/3 — consumed only, never encoded in template). `bracket-losers` is a required hook for the spoiler BYE logic.

- [ ] **Step 1: Scratch page against a real fixture playoff.** Create `pages/_spike-bracket.htm` (url `/_spike-bracket`, layout `default`) with `[PlayoffOverview]` + `[SpoilersToggle]`. Body: `{% component 'SpoilersToggle' callback='hlToggleSpoilers' %}` + `{% component 'PlayoffOverview' %}`. Point the fixture DB at a real playoff by URL param (the standalone route matches by slug). **Confirm a fixture playoff exists** — if the seeder has none, note it and prototype against the smallest bracket the fixtures can produce (or seed a temporary one via the dev plugin; do NOT modify rikki).

- [ ] **Step 2: Prototype the override markup + CSS technique.** Draft `partials/PlayoffOverview/default.htm` re-emitting the absolute-position engine verbatim: `<div class="bracket ...">` sized `{{ __SELF__.total_width }}rem × {{ __SELF__.total_height }}rem`; per `__SELF__.matches` a `<div class="bracket-{{ position_bracket_name }}" style="left:{{ offset_left }}rem;top:{{ offset_top }}rem;width:{{ __SELF__.match_width }}rem;height:{{ __SELF__.match_height }}rem">` wrapping the node card; then `<svg viewBox="0 0 {{ total_width*1000 }} {{ total_height*1000 }}">` with one `<polyline points="{{ points }}" fill="none" style="stroke-width:62.5">` per `__SELF__.polylines`. **Scope every new node/stroke class under a fresh ancestor class** (e.g. `.hl-bracket …`) with `NEW` class names so the auto-injected `heroeslounge.css` (loads last, wins) cannot target them. Node interior = restyled `.side`/`.tname`/score using the design tokens; TBD teams render a `disabled` name (excluded from spoiler toggling).

- [ ] **Step 3: Prove the spoiler-toggle data-walk.** Wire the `hlToggleSpoilers(show)` callback + the themed score-mask class; verify the SpoilersToggle cookie flips scores on/off, and reproduce the repeat-team hiding (first instance of each team name visible, later instances re-masked so you can't infer who advanced; `BYE!` placeholder team + `bracket-losers` special-cased). Neutralize any transition under reduced-motion.

- [ ] **Step 4: How to verify the spike.** Render a REAL fixture playoff at `/_spike-bracket` and confirm: (a) nodes land in bracket shape with connector polylines meeting node edges (no desync) across at least one single-elim (`se8`/`se16`) AND one double-elim (`de8`) type if fixtures allow — screenshot each; (b) `se64`/`de16` (if available) scroll rather than overflow the page; (c) the spoiler toggle masks/reveals scores and repeat-team names correctly; (d) no console errors, `storage/logs` clean. If a bracket type desyncs, the offsets/polylines are not being re-emitted verbatim — fix before declaring the approach proven.

- [ ] **Step 5: Capture SPIKE FINDINGS + revert the scratch page.**

  > **SPIKE FINDINGS (PROVEN — captured 2026-07-05):**
  > - **Chosen render approach — CONFIRMED.** Component-override at `partials/PlayoffOverview/default.htm` re-emitting the FROZEN geometry VERBATIM: each node absolutely placed at `left:{{offset_left}}rem;top:{{offset_top}}rem` inside the locked `{{match_width}}×{{match_height}}` (13×3.875rem) box via a `.hl-node` wrapper; connectors are the component's precomputed `polylines` drawn in one `<svg viewBox="0 0 {{total_width*1000}} {{total_height*1000}}">` sized `width/height:100%`, so 1000 SVG units == 1rem and `stroke-width:62.5` == ~1px. Wrapped in `.hl-bracket-scroll{overflow:auto}` (both axes). **Verified pixel-exact:** mapping every polyline endpoint through `svg.getScreenCTM()` and comparing to the nearest node edge gave a worst deviation of **0.00px** — se8 (12 endpoints) AND de8 (33 endpoints). No desync.
  > - **CSS technique that avoided the `heroeslounge.css` clobber — CONFIRMED.** Every node/stroke class is BRAND-NEW and scoped under a fresh `.hl-bracket` ancestor (`.hl-node`, `.hl-node-card`, `.hl-side`, `.hl-name`, `.hl-score`, `.hl-logo`, `.hl-when`, `.hl-bracket-nodes`, `.hl-bracket-links`, `.hl-link`). The auto-injected `heroeslounge.css` (loads LAST via `{% styles %}`) only targets `.bracket/.match.format-mini/.opponent/.name/.result/.spoiler/.notext/.state` — NONE of which appear in our markup (rendered-page count of `match format-mini` = **0**), so it cannot win. We re-supply the positioning context it would have provided ourselves: `.hl-bracket{position:relative}`, `.hl-bracket-nodes{position:absolute;top:0;left:0}`, `.hl-bracket-links{position:absolute;width:100%;height:100%}`. The ONLY legacy names reused are the `bracket-winners/bracket-losers/bracket-finals` wrapper hooks — consumed as JS/CSS-marker hooks (`bracket-losers` is load-bearing for the spoiler BYE branch), not as styled selectors.
  > - **Spoiler data-walk that reproduced repeat-team hiding — CONFIRMED.** Page-scoped `hlToggleSpoilers(show)` is a faithful port of the old `showHideSpoilersPlayoffView`, retargeted onto the `.hl-*` classes. Masking is class-based (`.is-masked`, shipped IN the markup so nothing leaks before JS runs); `SpoilersToggle` fires the callback on `ready` with the cookie value. `show>0` → strip `.is-masked` from all names/logos/scores. `show==0` → mask all `.hl-score`, reveal all names/logos, then walk `.hl-name:not(.hl-tbd)` in DOM order tracking `seenTeams`: a name already seen (+ its sibling `.hl-logo`) is re-masked; a `BYE!` node splices the PREVIOUS team out of `seenTeams` (so that team's next real appearance still reads as a first reveal) UNLESS the node is inside `.bracket-losers`, and un-masks that node's scores (a bye result isn't a spoiler). Verified live: Alpha Sloths (auto-advanced past a BYE in id 1) correctly reveals again at its next node while genuine repeats (semis/finals/losers bracket) stay masked; `showSpoilers` cookie persisted across an se8→de8 navigation.
  > - **Bracket types visually verified (fixture ids):** `se8` single-elim = playoff **id 1** "Season 30 Playoffs" (slug `season-30-playoffs`, 7 nodes incl. a real `BYE!` + advanced + TBD nodes); `de8` double-elim = playoff **id 2** "Community Cup" (slug `community-cup`, 14 nodes incl. a fully populated **losers bracket**). Both attached to Season 30 → the in-season route (resolve-by-title) and the standalone `/tournament/:slug` route both work. Screenshots captured off+on for each; 0 console errors/warnings; `storage/logs` clean of render/Twig errors.
  > - **Known residue (harmless):** `PlayoffOverview::onRun()` (frozen) still injects `ResizeSensor.js` + `ElementQueries.js` + `heroeslounge.css` — the CSS matches none of our classes and the ElementQuery scripts find no `[min-width~=]`/`[max-width~=]` attribute selectors to act on. Very tall brackets (`se64` 153rem) scroll at the PAGE level vertically (the wrapper sets no `max-height`, matching the frozen `#double_elim`); horizontal overflow IS trapped inside `.hl-bracket-scroll` (verified: 1412px bracket in a 760px viewport → wrapper scrolls, page `scrollWidth` stays ≤ viewport). TBD names use a new `.hl-tbd` class (excluded from the walk) rather than the legacy `.disabled`. Fixture-blind (fixtures only produce se8/de8): `se16/se32/se64/de4/de16/de8short/playoffv1-4/de6/DivSv1` geometries are unverified against real data — tracked for Task 4 / the real-dump pass.
  > - **Decision — the prototype override is KEPT.** Per the task lead's call, `partials/PlayoffOverview/default.htm` + its CSS (components.css bracket block + pages.css scroll wrapper) are RETAINED as the proven Task-4 foundation. This is harmless: no shipped page invokes `[PlayoffOverview]` until Task 4, so the override never renders on any existing page (confirmed no existing page regressed). Only the scratch PAGE (`pages/_spike-bracket.htm`) is reverted.
  > - **This scratch page is disposable — Task 4 rebuilds it as `pages/season/playoff.htm` + `pages/playoff/view.htm`.**

  Delete `pages/_spike-bracket.htm`.

- [ ] **Step 6: Verify** — `/_spike-bracket` is gone (404); the SPIKE FINDINGS note is filled in.

- [ ] **Step 7: Commit** — `git commit -m "feat(theme-next): playoff bracket render spike (throwaway; findings captured)"` (with the Fable trailer). Only the plan-note change lands; the scratch page was reverted.

---

## Task 1 — Shared match-card partial

The canonical param-driven `.match` card — highest-reuse leaf, built first. Extracted from the Phase-1 `home/results.htm` `.match` markup (which stays byte-identical this wave). Consumed by division round-matches (T3), match/view header context (T5), calendar (T6), team/view history (T7). **NOT a page — no INI of its own.** The feeder components (`RecentResults`, `UpcomingMatches`, `ViewMatch`) are all onRender-only and live on the CONSUMING surface; the card takes an already-loaded `match` model.

**Files:**
- Create: `themes/heroeslounge-next/partials/match/card.htm`
- Modify (only if a gap vs the existing `.match` classes): `themes/heroeslounge-next/assets/css/components.css`

- [ ] **Step 1: Define the param contract.** Header comment documenting params (all optional except `match`):
  - `match` (required Match model) — home/away default to `match.teams[0]`/`match.teams[1]`.
  - `variant` = `'result'` (default; played score card) | `'fixture'` (upcoming VS + date).
  - `revealScore` (bool, default true) — false renders the score inside the themed `.score-masked` span so the spoiler toggle can hide it (T3/T4 consumers pass false; home/results-style always-reveal passes true).
  - `withDate` (default true), `withDivision` (default true; `divtag` from `match.division.title|upper`), `withVod` (default true; `match.channels|first.url` when present), `withCaster` (default false; read-only accepted-casters line via `match.getAcceptedCasters()`), `size` (`'sm'`|`'lg'` shield passthrough, default `'sm'`), `timezone` (optional; when passed a component `.timezone`, human dates render viewer-TZ; when omitted, app-TZ day granularity — mirror `home/results.htm`).

- [ ] **Step 2: Card body.** Port the `.match chamfer` markup from `home/results.htm` (lines 63-80) into the partial, parameterised. Key bindings (all frozen Match accessors):
  - 3-state win/loss via `match.winner_id` vs team ids (`.side.win`/`.side.loss`; equal/null `winner_id` on a played match = neutral draw — acceptable, the `.side` model has no draw color).
  - Score: `home.pivot.team_score` / `away.pivot.team_score`, `wbp` is a RAW db string (no date cast) → always pipe `|date`; countdown uses `|date('c')` (lounge.js contract).
  - Date tag links `/match/view/{{ match.id }}`; team names link `/team/view/{{ team.slug }}` (SLUG); VOD → `(match.channels|first).url` (`target="_blank" rel="noopener"`).
  - Collapse playoff hierarchy to a single division/round tag here — the full bracket/round/match hierarchy is match/view-only (T5).

- [ ] **Step 3: Guards + empty branches.** `{% if home and away %}` (callers already skip <2-team matches; a defensive `TBD` opponent placeholder for the fixture variant). `fixture` + `withDate` falls back to `Unscheduled`/`TBD` when `wbp` is null (defensive — `UpcomingMatches` SQL excludes null `wbp`, so this is only reachable via `ViewMatch`/`RecentResults`). No channels → omit VOD; no accepted casters → omit caster line. **Whole-section empties belong to the CONSUMING partial, not the card.**

- [ ] **Step 4: CSS check.** The `.match`/`.match-top`/`.divtag`/`.vod`/`.match-body`/`.side`(`.right`/`.win`/`.loss`)/`.tname`/`.score`/`.s-win`/`.s-loss` classes ALREADY exist (components.css ~207-225). Add only the new `.score-masked` spoiler class here (blur-by-default; `body.reveal-spoilers .score-masked { filter:none }`), placed BEFORE the end-blocks, and add the transition to the reduced-motion block. If the card `<a>` links gain `.chamfer`, add them to the focus-affordance selector list.

- [ ] **Step 5: Verify (live, on a scratch consumer).** Temporarily render the card from `home/results.htm`'s data loop (or a scratch page) in both `variant='result'` and `variant='fixture'`; confirm it visually matches the existing home `.match` cards, scores/winner colors correct, links resolve. Remove scratch markup. **Do NOT refactor the Phase-1 home/dashboard consumers onto the card in this task** (optional later cleanup — call it out in the commit).

- [ ] **Step 6: Commit** — `git commit -m "feat(theme-next): shared match-card partial"`.

---

## Task 2 — Season overview `/:slug`

Packet: season-view. Old page `themes/HeroesLounge-Theme/pages/season/view.htm`. Two states driven by `season.reg_open`: the common **registration-closed** browse view (heading + Divisions link list + Playoffs link list — trivial) and the heavy **registration-open** `ParticipationOverview` view (signup forms + Teams/Free-Agents tabs).

**Files:**
- Create: `themes/heroeslounge-next/pages/season/view.htm`
- Create: `themes/heroeslounge-next/partials/SeasonOverview/default.htm` (component override)
- Create: `themes/heroeslounge-next/partials/season/overview.htm` (closed-state divisions/playoffs lists — **shared with the archive, Task 8**)
- Create (reg-open branch): `themes/heroeslounge-next/partials/participationOverview/default.htm` (component override)
- Modify: `themes/heroeslounge-next/assets/css/pages.css`

- [ ] **Step 1: Page shell (front-matter VERBATIM from old page).**
  ```ini
  title = "season"
  url = "/:slug"
  layout = "default"
  is_hidden = 0

  [SeasonOverview]
  ==
  {% component 'SeasonOverview' %}
  ```
  `SeasonOverview` has NO configurable properties. It populates in `init()` (not onRun/onRender) and sets `this.page.title = season.title`; **no `{% do onRender() %}` shim needed.** If `season.reg_open` is truthy it dynamically `addComponent()`s `participationOverview` (deferredBinding) — so `{% component 'participationOverview' %}` is valid ONLY inside the reg_open branch.

- [ ] **Step 2: SeasonOverview override.** `partials/SeasonOverview/default.htm` — prove the override resolves (temp marker), then: `{% if __SELF__.season %}` → `reg_open ? {% component 'participationOverview' id=season.id %} : (new-theme heading + {% partial 'season/overview' season=season %})`; `{% else %}` → styled empty state replicating the old literal **"No such season exists!"**.

- [ ] **Step 3: Closed-state `season/overview` partial (SHARED with Task 8).**
  - **Divisions** section (only if `season.divisions|length > 0`): heading "Divisions" + a list SORTED by title asc; each item links `/{{ season.slug }}/{{ division.slug }}` with text `division.title`. Style as `.p` panel / pill links (not Bootstrap `.list-group`).
  - **Playoffs/Tournaments/Stages** section (only if `season.playoffs|length > 0`): heading label switches on `season.type` — **1 → "Playoffs", 2 → "Tournaments", 3 → "Stages"** (plain int column; replicate the switch). Playoffs NOT sorted. Each links `/{{ season.slug }}/playoff/{{ playoff.title|url_encode }}` with text `playoff.title`.
  - Empty: a bare season renders just the heading (old theme shows nothing for empty sections; optional muted "Nothing scheduled yet").

- [ ] **Step 4: Reg-open `participationOverview` override.** Prove the RUNTIME alias resolves (see risk below). Re-skin the participation view, **keeping every `data-request` handler + field name intact** (frozen AJAX; the default layout's `{% framework extras %}` + lounge.js handle the swaps/toast). **Preserve the hidden POST fields exactly** — `ParticipationOverview::onTeamSignup` reads `season_id` + `team_id` from POST; a dropped hidden `<input>`/`data-request-data` key makes the handler silently no-op (easy to miss in a re-skin):
  - Signup controls (each gated exactly as old): `user` + captained teams + `!signedUp` → `data-request="…::onTeamSignup"` form with `<select name="team_id">` of `userCaptainedTeams`; `user` + `!signedUp` → "create a new team" link `/team/create` (literal, deferred); `user` + `!signedUp` + `user.sloth.region_id == season.region_id` → `data-request="…::onSlothSignup"` button; loop `free_agents` where agent==user.sloth → `data-request="…::onSlothRemoveSignUp"` button.
  - `.tabs` (lounge.js) Teams `[count]` / Free Agents `[count]` (replacing Bootstrap nav-tabs).
  - TEAMS tab: `season.teams` cards — `team/shield` + `/team/view/{{ team.slug }}` link; sloths with role icon + captain star (`sloth.pivot.is_captain`) + `/user/view/{{ sloth.id }}` link (literal, deferred); optional `team.short_description|striptags`.
  - FREE AGENTS tab: avatar + `/user/view/{{ agent.id }}` + role + battle_tag + discord_tag + country + birthday + desc.
  - Role/battlenet/discord SVGs are NOT confirmed present in the new theme — port them into `assets/img/roles/…` or swap to `site/icon` glyphs.

- [ ] **Step 5: Verify.** `/{{ a-fixture-season-slug }}` (closed state): heading + division links (sorted) + playoff links (correct label by type) all resolve to the T3/T4 URLs. Bad slug → styled "No such season exists!". **If the fixture DB has NO reg_open season, the participation branch can't be verified live — note it; the closed state may ship first and the reg-open override lands when a reg_open fixture exists.** Verify the `participationOverview` dynamic-alias override actually resolves (temp marker); if it silently falls back to Bootstrap, that is the primary risk.

- [ ] **Step 6: Commit** — split if convenient: `git commit -m "feat(theme-next): season overview (closed state)"` then `…"season overview participation (reg-open)"`.

**Risk / thin spot:** the closed state is trivial + low-risk; the reg_open branch is where all the complexity/risk sits (three AJAX signup forms + two card grids + a runtime-added alias override that DivisionTable-style resolution has only proven for statically-registered aliases). If no season currently has `reg_open`, ship the closed state and defer the participation override to when a reg_open fixture exists.

---

## Task 3 — Division page `/:slug/:divslug`

Packet: season-division. The richest read-only page: full standings + tabbed round-by-round results + Recent/Upcoming/Timeline sidebar + spoiler toggle. Old page renders `{% component 'DivisionOverview' %}` (a container that `addComponent()`s 5 leaves in `init()`). We attach the leaves DIRECTLY with their **registered CAPITALIZED aliases** and drive them via `onRender()` + custom markup (the container's lowercase leaf aliases — `divisionTable`, `roundMatches`, etc. — would miss the case-sensitive override lookup and fall back to Bootstrap).

**Files:**
- Create: `themes/heroeslounge-next/pages/season/division.htm`
- Create: `themes/heroeslounge-next/partials/division/{header,standings,rounds,recent,upcoming,timeline}.htm`
- Create (optional): `themes/heroeslounge-next/partials/SpoilersToggle/default.htm` (restyle switch; keep frozen `{% put scripts %}` + callback contract)
- Modify: `themes/heroeslounge-next/assets/css/pages.css`

- [ ] **Step 1: Page shell (front-matter — faithful + reuse-friendly).** Keep `[DivisionOverview]` (it resolves `.div` in `init()` AND sets `page.title` — keep it attached as the RESOLVER; do NOT render its default partial) + `[SpoilersToggle]`, and ADD the leaves with capitalized aliases:
  ```ini
  title = "Division"
  url = "/:slug/:divslug"
  layout = "default"
  is_hidden = 0

  [DivisionOverview]
  maxItems = 7
  daysInFuture = 7
  [SpoilersToggle]
  [DivisionTable]
  [RecentResults]
  type = "division"
  maxItems = 7
  [UpcomingMatches]
  type = "division"
  daysInFuture = 7
  showLogo = 1
  showName = 0
  showCasters = 0
  [RoundMatches]
  type = "division"
  showLogo = 1
  showName = 1
  [TimelineEntries]
  type = "division"
  maxItems = 15
  subsequent = 1
  ```
  Body: `{% set div = DivisionOverview.div %}{% if div %} …header/standings/rounds/sidebar… {% else %}` unknown-division state `{% endif %}`, then `{% component 'SpoilersToggle' callback='hlToggleSpoilers' %}` + a `{% put scripts %}` defining `hlToggleSpoilers(show){ document.body.classList.toggle('reveal-spoilers', show>0) }`.

- [ ] **Step 2: `division/header.htm`.** `h1 = div.season.title`, `h2 = div.overview_display_title ?: div.title` (nullable field), themed eyebrow/heading.

- [ ] **Step 3: `division/standings.htm` — FULL table (not the 5-row override).** `{% do DivisionTable.setProperty('id', div.id) %}{% do DivisionTable.onRender() %}`, then iterate ALL `DivisionTable.teams` into `.table`/`.tr` rows: `#` | shield + name → `/team/view/{{ team.slug }}` | `pivot.match_count` | `match_wins` | `map_wins` | `map_score` (signed, `+/-`). Row highlight when `selTeam`. Empty → single muted "No active teams yet" row. **Do NOT reuse `partials/DivisionTable/default.htm` (5-row cap).** N+1: `DivisionTable.teams` is a plain Support collection (no `.load()`) — shields lazy-load per row (≤~10 rows, acceptable).

- [ ] **Step 4: `division/rounds.htm` — tabbed round-by-round.** Guard `div.season.current_round > 0` (else hide entire section). `.tabs` (lounge.js `data-tabs`) with one tab per round `1..current_round`, **the LATEST round active by default** (`key == current_round - 1`). Per round: `{% do RoundMatches.setProperty('id', div.id) %}{% do RoundMatches.setProperty('round', i) %}{% do RoundMatches.onRender() %}`, then render each `RoundMatches.matches` entry via `partials/match/card.htm` (`variant='result'`, `revealScore=false`). Empty round → muted "No matches this round".

- [ ] **Step 5: `division/recent.htm` + `division/upcoming.htm` (sidebar).**
  - Recent: `{% do RecentResults.setProperty('id', div.id) %}{% do RecentResults.onRender() %}` → `.mrow`/`.res` rows, scores in the themed `.score-masked`. Eager-load `teams.smallLogo` (component only loads teams+logo). Empty → muted "No results yet".
  - Upcoming: `{% do UpcomingMatches.setProperty('id', div.id) %}{% do UpcomingMatches.onRender() %}` → date-grouped `.mrow` rows (channels/caster info), viewer-TZ via `UpcomingMatches.timezone`. Empty → re-skinned muted "Nothing scheduled" (replace the old jumbotron). Optional "full calendar" up-link `/calendar`.

- [ ] **Step 6: `division/timeline.htm` — NET-NEW re-skin of `TimelineEntries`.** `{% do TimelineEntries.setProperty('id', div.id) %}{% do TimelineEntries.onRender() %}` → iterate `TimelineEntries.timeline` with the full per-type switch (Sloth.Created/Logo/Deleted, Admin.Message, Match.Scheduled/Played, Team.Active/InActive/Created/Deleted/Logo/Message, Sloth.Joins/Left.Team) styled to tokens (drop the legacy `cd-timeline` CSS). Each entry: icon/image + sentence + `created_at|date('M d H:i')`; links `/team/view/{{ team.slug }}`, `/user/view/{{ sloth.id }}` (literal, deferred). Empty → muted "No activity yet". The `type='division'` branch is correct (the season branch has the `$allTimelines` bug — do not use it).

- [ ] **Step 7: Spoiler CSS + layout.** In pages.css add the division 2-col layout (main + sidebar → 1-col on mobile), round-tab panels, timeline, and the `.score-masked` blur (`filter:blur(...)` default; `body.reveal-spoilers .score-masked{filter:none}`). **Use a NEW class name — never `.spoiler`/`.notext`** (legacy `heroeslounge.css` is auto-injected by these components' `onRun`/`init` and loads last). Add the blur transition to the reduced-motion block; add any new focusable clipped elements to the focus-affordance list.

- [ ] **Step 8: Verify.** A fixture division page: full standings (all teams, correct signed map±), round tabs switch (latest selected), sidebar recent/upcoming/timeline populated or muted empty states, spoiler toggle masks/reveals scores AND persists via cookie across reload, no legacy Bootstrap markup leaking (case-sensitive alias check), `storage/logs` clean. Bad slug/divslug → "Unknown Division".

- [ ] **Step 9: Commit** — bite-sized: shell+header+standings; rounds; sidebar (recent/upcoming/timeline); spoiler wiring — each its own `feat(theme-next): division … ` commit.

---

## Task 4 — Playoff brackets (`/:season-slug/playoff/:playoff-title` + `/tournament/:playoff-title`)

Packet: playoff. Highest-complexity task; productionizes the Task-0 spike. Two pages share one component set (`PlayoffOverview` + `SpoilersToggle`); the in-season route resolves the playoff by **title**, the standalone by **slug** — after resolution BOTH render identical markup, so one shared override serves both. **Split into 4a (structure/layout) and 4b (nodes + spoiler + group stage + responsive).**

**Files:**
- Create: `themes/heroeslounge-next/pages/season/playoff.htm`
- Create: `themes/heroeslounge-next/pages/playoff/view.htm`
- Create: `themes/heroeslounge-next/partials/PlayoffOverview/default.htm` (bracket + participants override)
- Create: `themes/heroeslounge-next/partials/PlayoffOverview/divisionView.htm` (group-stage pane override)
- Modify: `themes/heroeslounge-next/partials/DivisionTable/default.htm` (extend for `showScore` mode) OR add a playoff-standings variant; add a `RoundMatches` re-skin for the group stage
- Modify: `themes/heroeslounge-next/assets/css/pages.css`

### Task 4a — bracket structure + layout

- [ ] **Step 1: Two page shells (front-matter VERBATIM).**
  ```ini
  # pages/season/playoff.htm
  title = "Playoff"
  url = "/:season-slug/playoff/:playoff-title"
  layout = "default"
  is_hidden = 0
  [PlayoffOverview]
  [SpoilersToggle]
  ```
  ```ini
  # pages/playoff/view.htm
  title = "Playoff"
  url = "/tournament/:playoff-title"
  layout = "default"
  is_hidden = 0
  contentType = "html"
  force_show = 0
  [PlayoffOverview]
  [SpoilersToggle]
  ```
  Body of each: `{% put scripts %}` the spoiler callback (season page callback name `showHideSpoilersSeasonPlayoff`, standalone `showHideSpoilersPlayoffView` — but retarget to the new class hooks; keep the callback NAMES the SpoilersToggle tag passes) + `{% component 'SpoilersToggle' callback='…' %}` + `{% component 'PlayoffOverview' %}`. `PlayoffOverview` splits its data: `init()` (always) populates `playoff`/`matches[]`/`polylines[]`/`total_width`/`total_height` and registers nested `roundMatches`/`divisionTable`; `onRender()` sets ONLY the timezone vars — **so keep the pages calling `{% component 'PlayoffOverview' %}` (onRun/onRender fire normally, no `{% do %}` needed) and drive the re-skin entirely through the override partials.**

- [ ] **Step 2: Productionize the bracket override.** Move the proven Task-0 markup into `partials/PlayoffOverview/default.htm`: **re-emit the frozen geometry verbatim** — `bracket` sized `{{ total_width }}rem × {{ total_height }}rem`; per `__SELF__.matches` an absolute-positioned `bracket-{{ position_bracket_name }}` node at `left:{{ offset_left }}rem;top:{{ offset_top }}rem;width:{{ __SELF__.match_width }}rem;height:{{ __SELF__.match_height }}rem`; the SVG `polylines` with `viewBox="0 0 {{ total_width*1000 }} {{ total_height*1000 }}"` and `stroke-width:62.5`. **Never change the 13×3.875rem box or the connectors desync.** Scope all new node/stroke classes under the fresh ancestor (from the spike) so `heroeslounge.css` can't win. Preserve the `bracket-losers` class hook.

- [ ] **Step 3: Scroll wrapper.** `#double_elim` (or renamed) wrapper with `overflow:auto` (BOTH axes) so `se64` (153rem tall) / `de16` (106rem wide) scroll instead of overflowing. Add to pages.css under the bracket section.

- [ ] **Step 4a Verify.** Both routes resolve (`/{{season.slug}}/playoff/{{title|url_encode}}` by title, `/tournament/{{playoff.slug}}` by slug) and render a bracket with aligned connectors; `null` playoff → old literal "We could not find a tournament with this title!". Screenshot ≥2 bracket types.

- [ ] **Step 4a Commit** — `git commit -m "feat(theme-next): playoff bracket structure + layout"`.

### Task 4b — nodes, spoiler, group stage, responsive

- [ ] **Step 5: Node interior + node link.** Restyle the interior of the locked box: team `shield`/name (TBD → `disabled` name, excluded from spoiler), score, scheduled `<time>` state (`wbp|date('d M', tz)` — human label, NOT a live countdown; lounge.js countdown is not used on brackets). Node `<a>` → `/match/view/{{ model.id }}`. If node `<a>` is chamfered, add it to the focus-affordance list.

- [ ] **Step 6: Spoiler system.** Wire the page callback (retargeted to the new class hooks) + `SpoilersToggle` cookie: default-mask all node names/logos/results; reveal on toggle; reproduce the repeat-team hiding (first instance visible, later re-masked) with `BYE!` + `bracket-losers` special-cased. **If you rename any hook you MUST update the page `{% put scripts %}` callback in lockstep; keep `disabled` on TBD names.** Neutralize any transition under reduced-motion.

- [ ] **Step 7: Group-stage pane (`divisionView.htm`).** Only when `playoff.divisions` non-empty: `.tabs` "Stage" per division (+ a "Knockout Stage" tab when `playoff.matches` non-empty). Per division: **extend the DivisionTable override for `showScore` mode** (adds Score + Map Score columns) OR add a dedicated playoff-standings partial — the existing override intentionally omits showScore; the nested `divisionTable` is attached with `showScore=TRUE`. Plus round tabs rendering the group-stage `RoundMatches` (`type='division'`) re-skinned to match. Null div → "Group not found". A pure-knockout playoff shows no Stage tabs; a pure-group playoff shows no bracket.

- [ ] **Step 8: reg_open participants branch.** When `playoff.reg_open`: `<h2>longTitle</h2>` + optional `onTeamSignup` signup form (team select from `userCaptainedTeams` + "Create a new team" → `/team/create`) + Participants card-deck (`team/shield`, `/team/view/{{ team.slug }}`, roster with role + captain star + `/user/view/{{ sloth.id }}`). Keep the frozen `data-request` handler/field names — **`PlayoffOverview::onTeamSignup` reads hidden POST `playoff_id` + `team_id`; preserve both or the signup silently no-ops.**

- [ ] **Step 9: Verify (fixtures) across bracket types.** Render a real double-elim AND a real single-elim (and, if fixtures allow, `se64`/`de16` for scroll + a groups+DE `playoffv1`); connectors stay aligned, spoiler masks/reveals + hides repeat teams, group-stage standings show scores, no `heroeslounge.css` clobber (node restyle survives), console + `storage/logs` clean, both routes resolve. Note in the commit which bracket types were fixture-verifiable (fixture-blind types tracked in PROGRESS.md pending the real dump).

- [ ] **Step 10: Commit** — `git commit -m "feat(theme-next): playoff bracket nodes, spoiler, group stage"`.

**Risk:** pixel-accurate node/line alignment across ~12 frozen `playoff.type` geometries; the auto-injected `heroeslounge.css` loading last; group-stage needing the un-ported `showScore`/`RoundMatches` overrides; the class-coupled spoiler jQuery (content-integrity, not just cosmetic). Data model + geometry are fully understood and FROZEN — risk is entirely faithful markup/CSS reproduction.

---

## Task 5 — Match detail `/match/view/:id`

Packet: match-view. `[ViewMatch]` (onRender-only; `Match::find(param('id'))`). **Split into 5a (header + pre-game rosters + scheduled state — the common case) and 5b (per-game statistics detail).** Real risk in 5b: the old game-stats table used DataTables.js + Bootstrap, NEITHER in the new theme.

**Files:**
- Create: `themes/heroeslounge-next/pages/match/view.htm`
- Create: `themes/heroeslounge-next/partials/match/header.htm`
- Create: `themes/heroeslounge-next/partials/match/team-roster.htm`
- Create (5b): `themes/heroeslounge-next/partials/match/game-card.htm`, `themes/heroeslounge-next/partials/match/game-stats.htm`
- Modify: `themes/heroeslounge-next/assets/css/pages.css`

### Task 5a — header + pre-game rosters + scheduled state

- [ ] **Step 1: Page shell (front-matter VERBATIM).**
  ```ini
  title = "View Match"
  url = "/match/view/:id"
  layout = "default"
  is_hidden = 0

  [ViewMatch]
  ==
  ```
  No page `[session]` (public — inherited from layout). Add a `<?php function onEnd()` that sets `this.page.title` from `ViewMatch.match` and 404s when the match is missing (mirror `pages/blog/post.htm`). `ViewMatch` is onRender-only — since we drive it via the component override / attached-component pattern, call `{% do ViewMatch.onRender() %}` before reading `ViewMatch.match` (do NOT change the component to deferred). Exposes: `match`, `decoded_playoff_position` (bracket/round/matchnumber via Cantor pairing — preserve exactly), `timezone`, `timezoneOffset`, `datetimeFormat`.

- [ ] **Step 2: `match/header.htm`.** Season/round heading (`match.division.season` title link + "Round N"); division-or-playoff hierarchy (`division.title` link OR playoff `longTitle` link); playoff context (Finals/Winner Bracket/Loser Bracket + "Round X - Match Y" from `decoded_playoff_position`, `bracket=3` Finals / `1` Winner / `2` Loser — preserve the Cantor decode exactly); Casters section ("Casted by:" `match.getAcceptedCasters()` titles + Twitch channel icon links; skip section when none). Scheduled state: `match.wbp|date(ViewMatch.datetimeFormat, ViewMatch.timezone)` + offset, OR the red literal **"The match has not been scheduled yet!"** when unscheduled (replicate exactly — TZ via the `|date(datetimeFormat, timezone)` filter or times render wrong).

- [ ] **Step 3: `match/team-roster.htm` (pre-game, when `match.games` is empty).** Two team cards (old Bootstrap `.card-deck` → new `.p` panels): 200px logo via `team/shield`, title link `/team/view/{{ team.slug }}`, `success`/`danger` outline for winner/loser by `winner_id`, player roster with role icons (`assets/img/roles/<role-title-slug>.svg`) + `/user/view/{{ sloth.id }}` links. Bye edge case: fewer than 2 teams / `getLoser()` null — guard null team relations.

- [ ] **Step 5a Verify.** A scheduled fixture match with no games: header hierarchy correct (season/round/division), casters shown or skipped, scheduled time in league TZ; an unscheduled match shows the red not-scheduled message; a playoff match shows the bracket context. `/match/view/999999` → 404. `storage/logs` clean.

- [ ] **Step 5a Commit** — `git commit -m "feat(theme-next): match detail header + pre-game rosters"`.

### Task 5b — per-game statistics

- [ ] **Step 4: Game tabs + `game-card.htm`.** When `match.games` non-empty: `.tabs` (lounge.js) "Game 1"…"Game N" (replacing the old Bootstrap navbar nav-tabs). Per game, render `game-stats.htm` via the `[gameStatistic]` component (alias `gameStatistic`, `GameStatistics.php`, onRender-only, page-var `__SELF__.game`; added in `ViewMatch::init()`) — `{% component 'gameStatistic' %}` fires its onRender.

- [ ] **Step 5: `game-stats.htm` — restyle WITHOUT DataTables.** Team logos (50px) + first-pick badge (`game.getFirstPickTeamId() == teamOne.id` floats left; Game uses explicit `teamOne`/`teamTwo` relations, NOT `teams[]`), hero picks (70×70 `assets/img/heroes/<hero.image_url>.png`), ban section (`teamOne/TwoFirst/Second/ThirdBan`), map (234×234 `assets/img/maps/bg_<map-title-slug>.jpg`) with replay download when `game.replay` present (else map only, no download), a toggle-collapsible stats table (kills/assists/deaths/siege/hero dmg/healing/dmg taken/exp — `primary/danger` row color for teamOne/teamTwo), and a talents table (7 columns: levels 1,4,7,10,13,16,20; `assets/img/talents/<talent.image_url>`). **Redesign these as themed `.table` markup — NO DataTables.js, NO Bootstrap `.collapse`/`.navbar`.** Sortable-table is OPTIONAL/DEFERRED (note it; do not port DataTables). Stats only render when `winner_id` exists (frozen component behavior).

- [ ] **Step 6: Image-path fallbacks.** Hero/map/talent/role images are theme-relative via `|theme` and derived from dynamic model fields — **missing/malformed URLs fail silently**; add a visible fallback (initials/placeholder tile) so a bad `image_url` doesn't leave a broken `<img>`. Flag which image dirs must be ported into the new theme's `assets/img/{heroes,maps,talents,roles}/`.

- [ ] **Step 5b Verify.** A fixture match WITH games: game tabs switch, hero/ban/map/talent images resolve (or show the fallback), stats + talents tables render themed and readable, collapsible toggle works without DataTables, no console errors from missing DataTables/Bootstrap. Note fixture-blind items (real replay files, full talent sets) for the real-dump pass.

- [ ] **Step 5b Commit** — `git commit -m "feat(theme-next): match detail per-game statistics"`.

**Risk:** DataTables/Bootstrap removed (must re-implement tables in theme CSS); `GameStatistics` frozen (only works when `winner_id` exists + `gameParticipations` eager-loaded); dynamic image URLs need fallbacks; Cantor playoff math fragile (preserve display exactly).

---

## Task 6 — Calendar `/calendar`

Packet: calendar. Global upcoming matches grouped by DATE (event-list, NOT a grid). `[UpcomingMatches]` global (`type="all"`, onRender-only). Caster-request display is read-only-rendered but the apply/retract buttons stay behind the SAME `user and can('cast_matches')` gate the old partial uses (replicate exactly — do not add/remove capability).

**Files:**
- Create: `themes/heroeslounge-next/pages/calendar.htm`
- Create: `themes/heroeslounge-next/partials/calendar/event-list.htm`
- Create: `themes/heroeslounge-next/partials/calendar/caster-requests.htm`
- Modify: `themes/heroeslounge-next/assets/css/pages.css`

- [ ] **Step 1: Page shell (front-matter VERBATIM).**
  ```ini
  title = "Calendar"
  url = "/calendar"
  layout = "default"
  is_hidden = 0

  [UpcomingMatches]
  daysInFuture = 100
  showLogo = 1
  showName = 1
  showCasters = 1
  type = "all"
  casterFilter = "off"
  ==
  ```
  Body calls `{% do UpcomingMatches.onRender() %}` then reads `UpcomingMatches.datesToMatches` (already in chronological order — the component runs `orderBy('wbp','asc')` before `groupBy`, and Laravel `groupBy` preserves first-appearance order, so **iterate the groups as-is; do NOT re-sort the `'d-M-y'` date keys** — a day-first string key does not sort chronologically), `.timezone`, `.timezoneOffset`, `.timeFormat`, `.user`.

- [ ] **Step 2: `calendar/event-list.htm`.** `ul`-style event list: one group per date (weekday `l` / day `d` / month `M` / year `y`, all in viewer TZ via `UpcomingMatches.timezone`). Per match row: kickoff time (`|date(timeFormat, timezone)`), division/playoff label + link (dual logic — `match.division.playoff` takes precedence over `match.playoff`; `division` with season → `/{{ season.slug }}/playoff/{{ title|url_encode }}`, else standalone `/tournament/{{ playoff.slug }}`, else division `/{{ season.slug }}/{{ division.slug }}`), optional `[REGION]` tag (only when both teams share `region_id` AND `showName`), per-channel Twitch icon links, both team shields + names → `/team/view/{{ team.slug }}` (or "TBD" for a bye/null team — guard null relations), central "VS" → `/match/view/{{ match.id }}`, and the caster column. **Reuse `partials/match/card.htm` where the row shape fits** (fixture variant with caster line); the date-grouping wrapper is calendar-specific.
  - Timezone info block: "All times in {{ UpcomingMatches.timezone }} {{ UpcomingMatches.timezoneOffset }}".

- [ ] **Step 3: `calendar/caster-requests.htm`.** Replicate the old `@casterRequests` DISPLAY: accepted casters (`getAcceptedCasters()`) as `/user/view/{{ caster.id }}` links with a mic icon; applied-casters tooltip. The interactive apply/retract controls stay gated `{% if user and can('cast_matches') %}` (RainLab.User `can()` — replicate the check EXACTLY, add/remove nothing), wired to the frozen `data-request="{{ __SELF__ }}::onCastRequest"` / `onCastRetract` with `data-request-data="match_id: X, caster_id: Y"` into the `#divCasterRequests{matchId}` container. **Known limitation:** the AJAX response re-renders the plugin's `@casterRequests` (Bootstrap) fragment into the container — the swapped fragment is un-skinned. Note this; the initial render is themed. (Caster enum: `pivot.approved` 0=pending / 1=accepted / 2=denied — not boolean.)

- [ ] **Step 4: Empty state.** Re-skin the old `.jumbotron` "Our little sloths are too lazy to schedule matches right now." as a themed muted panel. (Guard `user.username` — a guest can hit this page; do NOT reference `user.username` unconditionally like the old partial does.)

- [ ] **Step 5: Verify.** `/calendar` grouped by date in viewer TZ (change the browser TZ and confirm a time shifts + a date-group boundary moves), division/playoff links resolve, VS → match page, caster mic list shows, apply/retract buttons appear ONLY for a `can('cast_matches')` user, empty window shows the themed empty state (as a guest too). Note: `daysInFuture=100` can hydrate 500+ matches — confirm load time acceptable.

- [ ] **Step 6: Commit** — `git commit -m "feat(theme-next): calendar page"`.

---

## Task 7 — Team page `/team/view/:slug`

Packet: team-view. Public read-only team profile: banner + 4 tabs (Roster, Timeline, Matches, Statistics) + sidebar (description, current standing, recent results, upcoming). `[ViewTeam]` (resolves `.team` in `init()`, `addComponent()`s SIX camelCase sub-components rendered via `{% component 'alias' %}` in its default.htm). **PRESERVE the exact `[session]` config: the PAGE declares NO `[session]` block — security is inherited from the layout (`security="all"` = public). Also preserve the auth-gated roster split: guests see names+roles, logged-in users see the full player card (battle tags/discord).**

**Files:**
- Create: `themes/heroeslounge-next/pages/team/view.htm`
- Create: `themes/heroeslounge-next/partials/ViewTeam/default.htm` (component override)
- Create (alias-exact camelCase overrides): `themes/heroeslounge-next/partials/recentResults/default.htm`, `partials/divisionTable/default.htm`, `partials/upcomingMatches/default.htm`, `partials/roundMatches/default.htm`, `partials/timeLine/default.htm`, `partials/teamStatistics/default.htm`
- Create (optional): `themes/heroeslounge-next/partials/team/{roster,social}.htm`
- Modify: `themes/heroeslounge-next/assets/css/pages.css`; add icons to `site/icon.htm` (globe/website, discord, battlenet, captain crown — currently MISSING)

- [ ] **Step 1: Page shell (front-matter VERBATIM).**
  ```ini
  title = "View Team"
  url = "/team/view/:slug"
  layout = "default"
  is_hidden = 0

  [ViewTeam]
  maxItems = 5
  daysInFuture = 50

  [SpoilersToggle]
  ```
  Body: `<?php function onEnd()` setting `this.page.title = ViewTeam.team.title` + 404 when slug missing (mirror `blog/post.htm`); then `{% component 'SpoilersToggle' callback='hlToggleSpoilers' %}` + `{% component 'ViewTeam' %}`. **Do NOT invent a `[session]` block** — the layout carries `security="all"`.

- [ ] **Step 2: `ViewTeam/default.htm` override.** Prove it resolves (temp marker). Re-skin: full-bleed banner (team `banner`/`logo` via `team/shield` + name + `.socials` from `team.twitter_url`/`facebook_url`/`twitch_url`/`youtube_url`/`website_url`), `.tabs` bar (Roster / Timeline / Matches / Statistics). Keep the `{% component 'recentResults'|'divisionTable'|'upcomingMatches'|'roundMatches'|'timeLine'|'teamStatistics' %}` sub-calls (each fires its own onRender at render — no `{% do %}` needed; `teamStatistics` computes in `init()`). Slug not found → `ViewTeam.team` null → render a themed "Team not found" panel (page 404s via onEnd).

- [ ] **Step 3: Roster (auth-gated split).** `#general` tab: if `team.disbanded` → themed "Team disbanded" notice (old used `assets/img/icons/disbanded.png`); else a grid of `team.sloths`: card links `/user/view/{{ sloth.id }}`, avatar, captain star (`sloth.pivot.is_captain`), role icon + role title, `sloth.title`. Then a `{% if user %}` block (PRESERVE this gate) with per-player socials + `battle_tag` (battlenet icon; `heroesprofile_id` → external `https://www.heroesprofile.com/Player/{battletag}/{heroesprofile_id}/{region}`) + `discord_tag`. Missing role/battlenet/discord SVGs → add to `site/icon.htm` or port assets.

- [ ] **Step 4: camelCase sub-overrides (CASING TRAP).** ViewTeam registers `recentResults`, `divisionTable` (lowercase!), `upcomingMatches`, `roundMatches`, `timeLine`, `teamStatistics`. **The existing `partials/DivisionTable/` (capital) will NOT resolve for ViewTeam's `divisionTable`** on a case-sensitive FS — create a lowercase `partials/divisionTable/default.htm` (it can `{% partial 'DivisionTable/default' %}`-style delegate to the existing override body). Build each alias-exact override:
  - `recentResults/default.htm` → `.mrow`/`.res` rows (scores in `.score-masked`).
  - `divisionTable/default.htm` → the `teamId` window mode (`.you` highlight), `/team/view/{{ team.slug }}` links.
  - `upcomingMatches/default.htm` → `.mrow` rows with a CLEAN guest empty state ("Nothing scheduled" — the old jumbotron references `user.username` and BREAKS for guests on this public page).
  - `roundMatches/default.htm` → the Matches tab, re-authoring the group tabs (the plugin `@showMatchGroup` stays frozen; either re-author in-theme or inherit plugin markup — pick consistency).
  - `timeLine/default.htm` → re-skinned event feed (same per-type switch as Task 3's timeline).

- [ ] **Step 5: Sidebar.** Description (`team.short_description|striptags|raw` — skip if empty); per `team.active_divisions`: "Standings" + `/{{ div.season.slug }}/{{ div.slug }}` link (guard: `div.season` can be null for playoff divisions) + the `divisionTable` window; Recent Results; Upcoming Matches. Optional "see calendar" `/calendar`.

- [ ] **Step 6: Statistics tab (scope-cut candidate).** `teamStatistics/default.htm` + its `@stats`: a `data-request="onSeasonChange"` season `<select>` (participatedSeasons + all-time variants) + hero/map stat tables. **The old tab is a DataTables mini-app** — restyle the tables in theme CSS WITHOUT DataTables; the sortable/AJAX-season-change is OPTIONAL/DEFERRED (ship a static current-season table first, note the deferral). No participated seasons → "No statistics yet".

- [ ] **Step 7: Spoiler + CSS.** Wire `hlToggleSpoilers` against the re-skinned score markup (`.score-masked`, NOT `.f100`/`.score`/`.result` — legacy `heroeslounge.css` + `timeline.css` + `datatables.css` auto-inject and can clobber). Add team-banner/roster CSS to pages.css; add new focusable clipped elements to the focus-affordance list; neutralize any new animation under reduced-motion. `plain-fluid` was full-width — give the banner a full-bleed page container.

- [ ] **Step 8: Verify.** A fixture team page as a GUEST (roster shows names+roles only, no battle tags; upcoming shows clean empty state, not a `user.username` crash) AND logged in (full player cards). Tabs switch, sidebar standings/recent/upcoming populate, spoiler toggles, no Bootstrap leak (verify ALL camelCase alias overrides resolve — the primary risk). Disbanded team → notice. Bad slug → 404. Recommended phasing: banner + roster + sidebar first; Matches + Timeline next; Statistics last.

- [ ] **Step 9: Commit** — bite-sized per phase: `feat(theme-next): team page banner + roster + sidebar`; `…team page matches + timeline`; `…team page statistics`.

**Risk:** alias-case override silently falling back to Bootstrap (verify on case-sensitive FS); the Statistics DataTables mini-app (deferral candidate); preserving the guest-vs-auth roster split; legacy CSS/JS leakage; the guest-broken upcoming empty state.

---

## Task 8 — Season archive `/season/archive`

Packet: season-archive. Free-rider low-complexity page: list inactive (`is_active=0`) seasons grouped by region → division/playoff links. NO components — pure page `onStart()` data binding. **Reuses the `season/overview` partial built in Task 2.** Replace the old Bootstrap accordion with semantic `<details>/<summary>` + minimal CSS (NO new JS).

**Files:**
- Create: `themes/heroeslounge-next/pages/season/archive.htm`
- Modify: `themes/heroeslounge-next/assets/css/pages.css`
- Reuse: `themes/heroeslounge-next/partials/season/overview.htm` (from Task 2)

- [ ] **Step 1: Page shell (front-matter + onStart).**
  ```ini
  title = "Amateur Series Archive"
  url = "/season/archive"
  layout = "default"
  is_hidden = 0
  ==
  <?php
  function onStart()
  {
      $this['seasonsGroupedByRegion'] = \Rikki\Heroeslounge\Models\Season::where('type', 1)
          ->with('divisions', 'playoffs')
          ->where('is_active', 0)
          ->orderBy('created_at', 'desc')
          ->get()
          ->groupBy('region_id');
  }
  ?>
  ==
  ```
  (Replicate the old page's query VERBATIM — no `onRender` needed; pure Twig loops. Use the correct model namespace as the old page does.)

- [ ] **Step 2: `<details>/<summary>` markup.** Per region: `<h2>{{ season.region.title }}</h2>`; per season: `<details><summary>{{ season.title }}</summary>` + `{% partial 'season/overview' season=season %}` (the SAME partial as Task 2 — Divisions list → `/{{ season.slug }}/{{ division.slug }}`, Playoffs/Tournaments/Stages label by `season.type` → `/{{ season.slug }}/playoff/{{ playoff.title|url_encode }}`). Region relation casing lowercase (`divisions`/`playoffs`). If region iteration order matters, sort explicitly (region_id groupBy is insertion-order — non-deterministic risk noted in packet).

- [ ] **Step 3: Minimal CSS + empty state.** Style `<details>/<summary>` in pages.css (chamfer summary, chevron marker) — no JS. If `summary` gets a `clip-path`, add it to the focus-affordance list. Empty: no archived seasons → muted "No archived seasons." (fixture DB may have none — note it; seeder should include ≥1 archived season per region). Empty division/playoff sections are simply hidden by the `season/overview` guards.

- [ ] **Step 4: Verify.** `/season/archive`: regions with expandable seasons, links resolve to T3/T4 pages, keyboard-operable `<details>` (native), no Bootstrap `.card`/`.collapse`/`data-toggle` leakage. Empty DB → muted message.

- [ ] **Step 5: Commit** — `git commit -m "feat(theme-next): season archive"`.

---

## Task 9 — Wave 1 finishing pass

Mirrors Phase-1 Task 11 across all Wave-1 pages.

**Files:** various (fixes)

- [ ] **Step 1: Responsive sweep** — every Wave-1 page at 360 / 768 / 1200px: division 2-col → 1-col, bracket scroll wrapper, calendar rows, team banner, match-detail game tabs, standings `.tr` name column at 360px.

- [ ] **Step 2: Keyboard / focus** — tab through nav, all `.tabs`, `<details>`, bracket node links, spoiler switch, caster buttons; **every new focusable `.chamfer`/`clip-path` element has a visible inset ring** (confirm each was added to the focus-affordance selector list).

- [ ] **Step 3: prefers-reduced-motion** — spoiler-reveal transition static, bracket/any new animation static, `.dot`/ticker still neutralized; confirm the reduced-motion block stayed LAST in components.css.

- [ ] **Step 4: Timezone correctness** — calendar/division-upcoming/match-detail times reflect viewer TZ (change browser TZ, confirm a time AND a calendar date-group boundary shift); `SetTimezone` still rendered on the layout.

- [ ] **Step 5: Console + log hygiene** — browser console clean (no missing DataTables/Bootstrap/ResizeSensor errors surfacing as failures), `storage/logs` clean of theme Twig errors on every Wave-1 page.

- [ ] **Step 6: Cross-link resolution** — every new nav/cross-link emits a non-empty `href` and resolves: Wave-1 targets 200; deferred/dropped targets (`/user/view/:id`, `/team/create`, `/team/match/:slug`) are literal frozen URLs that 404 under the new theme (expected until Phase 3) — no `href=""` anywhere. Verify each literal matches the old theme `url =`.

- [ ] **Step 7: Update PROGRESS.md + report Wave 1 done** — record fixture-blind items pending the real DB dump (bracket types not in fixtures, real replay files, latin-ext roster glyphs, team logos) and any deferred sub-items (match-detail sortable tables, team Statistics AJAX season-change, calendar caster-request AJAX fragment skin, season reg-open participation if unshipped). Note Wave 2 (static content) is its own later plan; the ARAM item stays parked.

- [ ] **Step 8: Commit** — `git commit -m "feat(theme-next): wave 1 finishing pass"`.

---

## Verification philosophy

Unchanged from Phase 1: no theme PHP, so every task's Verify step is a live check against the running October instance (Docker, `http://localhost:8090`, fixture DB) — real data or a clean designed empty state, no 500s, no Twig/console errors, responsive + a11y. A task is not done while its page 500s, logs Twig errors, renders unstyled, or shows a blank where an empty state belongs. When a data binding can't be confirmed from code, confirm it in the browser against the DB before committing. The **real team DB dump is still pending** — when it lands, all Phase-1 and Wave-1 pages get a real-data re-verification pass (fixture-blind items tracked in `docs/superpowers/PROGRESS.md`).
