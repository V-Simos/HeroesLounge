# UI Rework — Progress Tracker

**This file is the single source of truth for execution progress.** Update it at
every task/review transition. (The plan file's inline checkboxes are NOT
maintained — this table is.)

> **Fresh session?** Read `docs/superpowers/NEXT-SESSION.md` first — it's the launch pad.

- Phase 1 spec: `docs/superpowers/specs/2026-07-03-ui-ux-rework-design.md`
- Phase 1 plan: `docs/superpowers/plans/2026-07-03-ui-rework-phase-1.md` — ✅ DONE (PR #1 open)
- Phase 2 spec: `docs/superpowers/specs/2026-07-04-phase-2-public-reskin-design.md`
- **Phase 2 Wave 1 plan (NEXT TO EXECUTE):** `docs/superpowers/plans/2026-07-04-phase-2-wave-1-viewing.md`
- Branch: `ui-rework` (fork V-Simos/HeroesLounge, upstream Fabian-Sommer/HeroesLounge).
  Phase 2 recommended to start on a new `ui-rework-phase-2` branch off `ui-rework` (not yet created).
- Process: superpowers subagent-driven development — per task: fresh implementer
  subagent → spec-compliance review → code-quality review → (fixes → re-review) → next task.

## Task status (Phase 1)

| Task | Implemented | Spec review | Quality review | Commits |
|---|---|---|---|---|
| 0 — Verify dev env | ✅ resolved: no Vagrant/dump; Docker chosen | n/a | n/a | 7b4d7d2 (plan amendment) |
| 0.5 — Docker dev env + fixtures | ✅ | ✅ | ✅ (fixes applied + re-approved) | 806e802, b2f7d5e, 520336a, 902c7c6, 559d994 |
| 1 — Theme skeleton | ✅ | ✅ | ✅ approved (minor notes → Task 6) | 619f7db |
| 2 — Self-hosted fonts | ✅ | ✅ | ✅ | d932116 |
| 3 — Design tokens + base styles | ✅ | ✅ | ✅ | 0a94652 |
| 4 — Component library CSS | ✅ | ✅ | ✅ (fixes applied + re-approved) | c64e3ee, 0ff895b |
| 5 — lounge.js | ✅ | ✅ | ✅ (fast-follows applied + re-approved) | 271c199, 7c96aad |
| 6 — Layouts + site chrome | ✅ | ✅ | ✅ (fixes applied + re-approved) | 53f3262, 9e30f30 |
| 7 — Homepage static sections | ✅ | ✅ | ✅ | f006b55 |
| 8 — Homepage data sections | ✅ | ✅ | ✅ (fixes applied + re-approved) | 8350276, 27a25dd |
| 9 — Dashboard (logged-in home) | ✅ | ✅ | ✅ (follow-ups applied) | 3c8adcf, 7dd9fde |
| 10 — Blog pages | ✅ | ✅ | ✅ (fixes applied + re-approved) | 9330c5d, 0d0347f |
| 11 — Maintenance + finishing pass | ✅ | ✅ | ✅ | 74f4d7f, 2ec1cec, dc20200 |

## Task status (Phase 2 — Wave 1: public competitive viewing)

Plan: `docs/superpowers/plans/2026-07-04-phase-2-wave-1-viewing.md`. IN PROGRESS
(subagent-driven, on branch `ui-rework-phase-2` off `ui-rework`).

| Task | Implemented | Spec review | Quality review | Commits |
|---|---|---|---|---|
| 0 — Bracket-render spike (throwaway) | ✅ | ✅ | ✅ (approve-with-nits → fixed) | 160ac0c, 69028c1, 93b79ae |
| 1 — Shared match-card partial | ✅ | ✅ | ✅ (nits fixed + verified) | 275fe2b, 4795a9c |
| 2 — Season overview `/:slug` | ✅ (closed state) | ✅ | ✅ (nits fixed + verified) | 4b59d0f, 19c0222 |
| 3 — Division page `/:slug/:divslug` | ✅ (+ design rework) | ✅ | ✅ design-rework review approved (original nits deferred — see notes) | edbc0bd, 1c4f677, 8e7bdd1, 5ab67d6, cde17ef |
| 4 — Playoff brackets (4a + 4b) | — | — | — | |
| 5 — Match detail `/match/view/:id` (5a + 5b) | — | — | — | |
| 6 — Calendar `/calendar` | — | — | — | |
| 7 — Team page `/team/view/:slug` | — | — | — | |
| 8 — Season archive `/season/archive` | — | — | — | |
| 9 — Wave 1 finishing pass | — | — | — | |

**Wave 2 (static content) = a separate later plan, not yet written.**

### Notes from Task 0 (bracket spike — proven, de-risked)

- **Fixtures had ZERO playoffs** (blocked live bracket verification). The dev
  seeder (`plugins/dev/fixtures/console/SeedFixtures.php` → new `seedPlayoffs()`,
  NOT frozen) now seeds **se8** (id 1, slug `season-30-playoffs`) + **de8**
  (id 2, slug `community-cup`), both attached to Season 30, with early rounds
  resolved via the real `Match::afterSave` advancement path so teams re-appear
  in later nodes (exercises spoiler repeat-team hiding + a real BYE). Idempotent
  under `fixtures:seed --force`. **Task 2/4/6 depend on this seeding.**
- **Proven render approach (Task 4 productionizes):** override at
  `partials/PlayoffOverview/default.htm` (component `PlayoffOverview` lives in
  **`plugins/rikki/loungeviews`**, NOT heroeslounge) re-emits the frozen geometry
  VERBATIM — absolute nodes in the locked 13×3.875rem box + one SVG of
  `polylines` (`viewBox 0 0 total_width*1000 total_height*1000`, `stroke-width
  62.5`). Measured **0.00px** connector-to-node deviation on both se8 + de8.
- **Clobber beaten:** all bracket classes freshly scoped under `.hl-bracket`
  (`.hl-node/.hl-side/.hl-name/.hl-score/.hl-logo/…`) so the last-loaded frozen
  `heroeslounge.css` can't target them; only `bracket-winners/losers/finals`
  reused as JS/marker hooks. Overflow via `.hl-bracket-scroll{overflow:auto}`.
- **Spoiler:** names/scores ship `is-masked` by default; a **page-level**
  `hlToggleSpoilers(show)` toggles `body.reveal-spoilers`. That callback lived on
  the reverted scratch page (NOT in repo). **Task 4 ports it** from the frozen
  old-theme callbacks `showHideSpoilersPlayoffView` (playoff/view.htm) +
  `showHideSpoilersSeasonPlayoff` (season/playoff.htm), retargeted onto `.hl-*`.
  The kept partial is INERT until then (no page attaches `[PlayoffOverview]`).
- **Fixture-blind bracket types** (not producible by current fixtures — verify
  in Task 4 / real-dump pass): `se16, se32, se64, de4, de16, de8short,
  playoffv1-4, de6, DivSv1`; also `se64` vertical-scroll + group-stage
  (`divisionView`) branch. Geometry is frozen so the same markup should serve.
- **Seeder infra note:** `seedPlayoffs()` relaxes then RESTORES its connection
  `sql_mode` (the frozen `createMatches()` inserts match rows without the
  NOT-NULL-no-default `is_played`; prod MySQL runs non-strict). Dev-only, scoped.

### Notes from Task 1 (shared match-card partial)

- **`partials/match/card.htm`** is the canonical param-driven `.match` card.
  Contract: `match` (req); `home`/`away` (default `match.teams[0]`/`[1]`);
  `variant` `'result'`|`'fixture'`; `revealScore` (false → score wrapped in
  `.score-masked`); `withDate`/`withDivision`/`withVod`/`withCaster`; `size`
  (`'sm'`/`'lg'` shield passthrough); `timezone` (viewer-TZ when passed, else
  app-TZ day granularity). Consumers: T3 division rounds (`revealScore=false`),
  T5 match view, T6 calendar, T7 team history. Booleans use `is defined ? x :
  default` (NOT `|default`) so a passed `false` is honored.
- **`.score-masked`** (components.css): CSS-only spoiler mask (blur 7px default;
  revealed by `body.reveal-spoilers`). **Distinct from the bracket's JS-managed
  `.is-masked`** (transparent block). Intentional visual divergence — **Task 4
  reconciles the two spoiler languages.**
- **divtag is DIVISION-ONLY.** A playoff match with a null division renders NO
  context tag (deliberate — no Cantor/round decode in this leaf; that's T5).
  T6/T7 own their playoff-match labeling (pass `withDivision=false` + render
  their own) — decide when those consumers are built. Documented in the header.
- **Twig `_self` macro gotcha:** the card de-dupes score markup via
  `{{ _self.scoreInner(...) }}`. Works + auto-safe on this stack's **Twig
  2.14.4** (MacroAutoImportNodeVisitor); would break on plain Twig 2.x without
  it. Remember if the engine is ever upgraded.
- **Not refactored (optional later cleanup):** Phase-1 `home/results.htm` +
  `dashboard/results.htm` still carry their own inline `.match` markup — not yet
  delegated to the shared card (byte-identical this wave; noted in commit).

### Notes from Task 2 (season overview `/:slug` — closed state)

- **Shipped:** `pages/season/view.htm` + `partials/SeasonOverview/default.htm`
  (component override, casing `SeasonOverview`) + `partials/season/overview.htm`
  (divisions/playoffs lists). Closed state verified live on `/season-30`
  (3 sorted division links + 2 url-encoded playoff links; `type=1` → "Playoffs").
- **`partials/season/overview.htm` is SHARED with Task 8** — param-driven on
  `season` only (no `__SELF__`/component coupling); the season-title heading
  lives in the OVERRIDE, not this partial, so the archive supplies its own
  per-season heading. Responsive `auto-fit` grid handles lone-section seasons.
- **reg_open participation is DEFERRED** (Season 30 is `reg_open=0` → the heavy
  ParticipationOverview branch is unverifiable). The override's reg_open branch
  calls `{% component 'participationOverview' id=season.id %}` VERBATIM
  (functional but un-skinned) with a `TODO(wave-1 deferred)`. **Build the themed
  `partials/participationOverview/default.htm` when a reg_open fixture exists**
  (or the real dump lands with one) — flagged as the primary risk (runtime-added
  deferred alias; override-resolution unproven for dynamic aliases).
- **Faithful carryover (do NOT "fix"):** the frozen division sort
  `|sort((a,b) => a.title > b.title)` uses a boolean comparator (Twig quirk) —
  replicated as-is per the re-skin mandate.

### Notes from Task 3 (division page — DONE; design rework reviewed ✅)

**STATUS: done.** The original 4 commits + a user-requested **design rework**
(2026-07-09, commit `cde17ef` — full-width round rows, mini-card recent results,
round timeline photos; spec `specs/2026-07-09-division-page-rework-design.md`,
spec-review passed + code-quality review APPROVED). The rework review was scoped
**design-only per user mandate** ("we are only changing the design, we don't
touch any other issues"), so the three original open items below were NOT
addressed and remain **consciously deferred**, not forgotten:
  1. Timeline spoiler leak (unmasked `Match.Played` score) — deferred.
  2. `pages.css` reduced-motion block ordering — deferred (no functional impact).
  3. Standings empty-state wording ("...yet") — deferred (cosmetic).
Design-rework review left one non-blocking observation (accepted as-is): reusing
`.match` in the sidebar means `.match:hover{background:--panel2}` now highlights
recent-result rows on hover — verified live, reads as intentional row-highlighting.
**Pre-existing bug surfaced (out of scope, tracked below):** the frozen
`TimelineEntries::onRender()` division branch memory-500 on OLD divisions.

- **Built:** `pages/season/division.htm` + `partials/division/{header,standings,
  rounds,recent,upcoming,timeline}.htm` + `partials/SpoilersToggle/default.htm`
  (re-skinned switch, frozen `{% put scripts %}` kept byte-for-byte) + CSS.
  Verified live on `/season-30/division-1`: standings (3 teams, signed map±),
  round tabs (R3 active by default, switching works), sidebar recent/upcoming/
  timeline populated, spoiler toggle masks/reveals + persists via cookie, ZERO
  Bootstrap leak, clean console/logs, bad slug → themed "Unknown division".
- **Standings bindings** (confirmed vs `Division::getDivisionTableStandings()`,
  heroeslounge/models/Division.php): `pivot.match_count`, `match_wins`,
  `map_wins`, signed `map_score`. `DivisionTable.teams` is a plain Support
  collection (no `.load()`). Full table — did NOT reuse the 5-row override.
- **Timeline** uses the `type='division'` + `subsequent=1` chunk branch
  (`$someTimelines`) — correctly AVOIDS the season branch's `$allTimelines` bug.
  Switch ported from `plugins/rikki/loungeviews/components/timelineentries/default.htm`.
- **Spec-review adjudications (accepted):** (A) round cards use
  `variant = is_played ? 'result' : 'fixture'` — round 3 is unplayed, so a forced
  `result` would show a misleading masked 0:0; correct improvement. (B)
  `withDivision=false` on round cards (redundant DIV tag). (C) recent sidebar
  uses text team names (matches dashboard, narrow sidebar) not shields.
- **OPEN items for the quality review / fixes (decide next session):**
  1. **Timeline spoiler leak:** `Match.Played` entries render the score
     (e.g. "ended 2:1") UNMASKED while spoilers are OFF — faithful to the frozen
     plugin, but inconsistent with the prominent on-page spoiler toggle. DECIDE:
     wrap that score in the existing `.score-masked` (small, consistent) vs leave
     faithful. (The pending quality-review prompt asks the reviewer to recommend.)
  2. `pages.css`: the new division block was appended AFTER pages.css's own small
     `@media (prefers-reduced-motion)` block (components.css's is correctly LAST).
     No functional impact (new pages.css rules add no transitions) — confirm/tidy.
  3. Standings empty state reads "No active teams **yet**" vs spec "No active
     teams" — cosmetic.

## Phase 1 status (archived)

**✅ PHASE 1 COMPLETE + final whole-theme review passed.**
All 11 tasks through spec + quality review; final cross-cutting review (commit
`60eb94b`) found & fixed one AA regression (chamfered `.post`/`.event` card
links had no visible keyboard focus ring — the Task-4 inset-ring convention
wasn't extended to cards added in Tasks 7/8/10; fixed + verified live) and the
standings eyebrow separator. Ready for `finishing-a-development-branch`.

**Deferred from final review (Phase-2 / polish — non-blocking):**
- Dashboard `.p-head` panel labels are `<div>/<span>`, not headings → SR heading
  nav skips dashboard content. Promote to `h2` or `aria-labelledby` the panels.
- External-link open behavior inconsistent (stream/VOD new-tab; hero/Discord/
  socials same-tab) — pick one convention.
- Unused icon branches `user-plus`, `newspaper` in site/icon.htm (seeded per
  Task 6 plan; keep for Phase 2 or drop).
- `.input:focus` dead `outline-offset:2px` (components.css:320) — harmlessly
  overridden by the -4px affordance rule; left byte-intact per Task 5's
  mandated-form-block preservation.

**Line-ending note (investigated Task 11):** a Task-11 concern claimed the
whole repo is CRLF. VERIFIED FALSE at byte level (`git ls-files --eol` +
`xxd`): all rework blobs are **LF**, matching the upstream LF baseline. The
`git cat-file | grep -c $'\r'` check that raised it is a false positive
(`grep -c` counts lines, not CRs). No remediation needed.

**Notes from Task 10 (blog):**
- blog/post-card.htm is the single source for .post card markup (home/posts
  delegates); events cards are the distinct .event variant.
- Pager is windowed (1 … current±2 … last, ≤9 entries, clamped out-of-range,
  page-1 emits param-less URL — verified against Rain Router source).
- Real-data re-verify list (blocked on dump): prose typography beyond <p>
  (headings/blockquote/code/img exercised only via temp content), pager at
  real post volume, post cover image + carousel/files blocks (not ported),
  post-not-found semantics after real Indikator swap (redirectPage may fire
  before onEnd 404 — dev/README checklist).
- icon.htm at 21 branches — fine; soft ceiling ~30 branches or when two
  consumers need sizing variants, then revisit (dynamic partial / macro).
- tag_posts is a graceful stub (old theme's was an inert empty page).

**Notes from Task 9 (dashboard):**
- **Task 11 finishing-pass items:** extract `partials/site/initials.htm` and
  replace the 5 initials copies (team/shield.htm, site/nav.htm,
  dashboard/{welcome,standings,matches}.htm) — own small commit; guard
  "ROUND 0 OF N" pre-season edge (welcome + home/standings eyebrows);
  standings `.tr` name column very narrow at 360px (hard wraps).
- **Phase-2 plugin backlog:** the cross-team merge scaffolds
  (dashboard/next-match.htm + matches.htm, kept in sync by comments) belong
  in a plugin component/helper when plugins unfreeze; "Propose time" pill is
  dead — NO UpcomingMatches type can return NULL-wbp matches (SQL excludes);
  notifications pipeline dead upstream (Session::put commented out).
- Dashboard verified against DB truth for AlphaCap + DoubleDuty (two-team);
  no team-less fixture user exists — empty states verified from guards only.
- Frontend login uses EMAIL (alphacap@dev.local), not username (dev/README
  fixed).

**Patterns established by Task 8 — Task 9 must reuse, not reinvent:**
- **onRender pattern:** UpcomingMatches + RecentResults collect data in
  `onRender()` ONLY (no onRun); partials attach the component in front-matter
  then call `{% do Component.onRender() %}` (+ `setProperty` for runtime ids)
  and read the public property. `{% component %}` would render the plugin's
  Bootstrap partial. Verified against Controller.php:1213 on this pinned
  October v1 — re-verify after any core upgrade (if core ever auto-calls
  onRender for partial components, every `{% do %}` site double-queries).
- **DivisionTable override** (`partials/DivisionTable/default.htm`): emits
  header+rows only; consumer supplies wrapper (`.table`/`.p` panel) +
  table-foot. Props: `id`, `teamId`, `surroundingEntries` (default 4;
  `maxEntries` is dead). startIndex is clamped in the override (plugin
  computes negative for small divisions — upstream partial has the bug live).
  `showScore` playoff mode NOT ported — add a guard before theming playoffs.
  Old partial's `user.sloth.isInTeam()` highlight dropped (moot: use teamId).
- **Season derivation:** canonical copy + rationale in site/nav.htm; 5 sites
  in sync (nav, footer, hero, results, standings).
- Eager-load lazy relations after onRender (`.load(...)`) — but NOT on
  DivisionTable's teams (plain Support\Collection, no load()).
- Shield partial: `{% partial 'team/shield' team=t size='lg'|'sm' %}`; logo
  branch untested by fixtures (no team logos seeded) — verify with real dump
  (same for dashboard teamchip `.pic img` logo branch in welcome.htm).

**Pre-production hardening (plugin-side, frozen for now — MUST be tracked to
production cutover; from Task 8 quality review):**
- `Division.php:190` `Log::info(...)` serializes full standings into the log
  on EVERY DivisionTable render (3×/homepage hit) — log bloat + CPU.
- `DivisionTable::onRender()` runs a dead teams query (overwritten result).
- `getDivisionTableStandings()` lazy-loads games + winner/loser per match —
  query cascade grows with season length, ×3 divisions per homepage hit.
- UpcomingMatches type=all hydrates every unplayed match in 14 days (+5
  eager relations) to show one card — bounded by time, not count.
- **`TimelineEntries::onRender()` division/season branch memory-500** (found
  2026-07-09): with `subsequent=1` it `chunk(100)`s the ENTIRE global timeline
  table with heavy eager-loads (`matches.teams.divisions`, `sloths.teams.divisions`,
  `teams.logo`, …), filtering in PHP until it collects `maxItems`. For OLD
  divisions whose entries sit deep in the table it scans most of it and blows
  past PHP's 128 MB → the division page 500s (confirmed pre-existing; recent
  divisions like `eu-season-23` resolve cheaply). Fix at cutover: filter the
  timeline in SQL (join on the division/season) instead of scan-in-PHP.
- No component caching (October v1) — decide caching strategy for the
  anonymous homepage before cutover.
- **Dashboard multiplier (Task 9):** logged-in home runs UpcomingMatches
  onRender 2×teams (next-match + matches duplicate the query set),
  RecentResults ×distinct divisions, DivisionTable ×(team×division) — and can
  never be page-cached. The Division.php Log::info + standings cascade above
  fire on every dashboard hit too. Fold the duplicate UpcomingMatches runs
  into one source when plugins unfreeze.

**Notes from Task 7 reviews:**
- Task 8 MUST replace hero's placeholder eyebrow (`EU · SEASON — · ROUND —`)
  and `— divisions` chip with live data AND delete the TODO(task-8) comments
  in the same commit; cast panel fills hero's empty second grid column.
- `.hero` background in pages.css is page-scoped by convention only — if a
  future page reuses class="hero", scope it then.
- `.how` items are divs (mockup fidelity); ul/li would be better SR semantics
  — optional deviation, only with team buy-in.

**Notes from Task 6 (site chrome):**
- Shared chrome lives in `partials/site/head.htm` + `partials/site/scripts.htm`
  — future CSS links/preloads/meta go THERE (both layouts consume them).
- SetTimezone must stay rendered (`{% component 'SetTimezone' %}` in
  default.htm before the scripts partial) — attach-only never detects tz.
- `{% scripts %}` is LAST in scripts.htm (component-injected JS needs jQuery).
- Skip link + `main#main` landmark exist; `html{scroll-padding-top:84px}`.
- Nav Guides → `/guides` (old-theme landing page); bell → `/user` placeholder
  (no notifications URL exists); bell count badge omitted (no data source).
- Literal URLs (calendar, guides, faq, …) 404 under the new theme until their
  phases port those pages — expected, frozen URL strings verified vs old theme.
- Deferred polish: Escape-to-close burger; favicon manifest/theme-color zoo;
  `lang="en"` hardcoded until localization is real.

**Contracts established by Task 5 (lounge.js) — Tasks 6–9 templates must follow:**
- Countdowns: emit datetimes with Twig `|date('c')` (offset-qualified ISO).
  Timezone-less strings parse as viewer-local = silently wrong. Rescan on
  October `ajaxUpdateComplete` is wired (AJAX-swapped partials tick).
- Tabs: `.tabs > .tab` buttons with `data-tab-target` in a `[data-tabs]`
  container; panels toggled via `hidden`. ARIA tab semantics only if the
  template provides role=tablist/tab/tabpanel + aria-controls.
- Mobile nav: lounge.js expects `#burger` and `#site-links` ids.
- Toasts: showToast is IIFE-private by design; if success/flash toasts are
  needed later, expose one namespaced global — do NOT write a second impl.
- jQuery 1.12.4 vendored (byte-copy of old theme, required by October v1
  framework.js). Post-rework spike: upgrade to 3.7.x (framework floor 1.9.1).
- Deferred polish: toast keyboard-dismiss/hover-pause; outer-tab click resets
  nested inner-tabs state (accepted — nested tabs unplanned).

**Notes from Task 4 reviews:**
- **Do not revert:** the "focus affordance on clipped elements" section at the
  end of components.css (outline-offset -4px). clip-path clips outlines drawn
  outside the border box — the mockups' +3px offset is invisible on chamfered
  elements (WCAG 2.4.7). A fidelity pass must NOT restore the mockup behavior.
- Design-pass items (fidelity-frozen for now): `.pill.cast` ≈4.4:1 contrast
  (sub-AA at 10px); `.btn-solid` focus ring is storm-on-storm (visible but
  modest); `.avatar .pic`/`.teamchip .pic` duplicate `.badge-hue-7` gradient;
  font-family literals repeated ~15× are token candidates when the freeze lifts.
- `.tr:not(.th):hover` from reference-design deliberately not ported
  (dashboard-v2's row system has no hover) — one-line add if Task 7/8 wants it.

**Notes from Task 3 reviews (plan already amended for the first two):**
- Task 4 must move the `prefers-reduced-motion` block from base.css to the END
  of components.css (source-order cascade; media queries add no specificity).
- Task 10 must add prose link affordance (underline + storm) inside post
  content — base.css strips link styling globally.
- Production cutover (Phase 3+): decide CSS cache-busting (`?v=` vs October
  combiner). Plain `| theme` links have no Cache-Control today.
- `--deep` token is not in dashboard-v2's block (only reference-design); it's
  included in tokens.css with a source comment — intentional, keep.

**Notes from Task 2 reviews:**
- 8 woff2 files, not 9 — the plan header said 9 but the variant list totals 8
  (plan doc fixed). Weight↔file mapping verified at binary level (fonttools).
- Latin-only subset lacks latin-ext glyphs (ł, č, ş, ő…) — EU roster names will
  per-glyph fall back mid-heading. Watch during visual QA with real data; fix
  would be adding latin-ext subsets (Phase-level decision, not a Task 2 defect).
- For Task 3 wiring: load fonts.css before other CSS; preload only
  chakra-petch-700 + barlow-regular with `crossorigin` (required even
  same-origin); no italic faces shipped → `<em>` synthesizes oblique (check one
  real blog post during QA).

**Deferred minor notes from Task 1 quality review (address in Task 6 when
default.htm is rewritten):**
- theme.yaml `require` list is aspirational (lists RainLab.Blog unused; omits
  RainLab.User / Rikki.LoungeViews that the layout actually uses) — true up or comment.
- `<title>{{ this.page.title }} — Heroes Lounge</title>` renders a dangling
  "— Heroes Lounge" if a page has an empty title.
- Layout has no `{% styles %}` / `{% scripts %}` yet — required before plugin
  components that inject assets (Task 6 adds them). ~~CSRF meta~~ NOT needed:
  this install's framework.js reads the XSRF-TOKEN cookie, not a meta tag
  (verified in container during Task 5 review).
- `lang="en"` hardcoded; switch to active locale when translation work starts.

## Resuming a session

1. Ensure Docker Desktop is running, then from repo root:
   `docker compose -f dev/docker-compose.yml up -d`
   (containers don't auto-start after reboot; DB data persists in the volume).
2. Site: http://localhost:8090 — should show the `heroeslounge-next` placeholder
   ("heroeslounge-next lives"). If it shows the OLD theme, re-run
   `docker compose -f dev/docker-compose.yml exec -T web php artisan theme:use heroeslounge-next`.
3. If the DB is ever empty/broken: reseed with
   `docker compose -f dev/docker-compose.yml exec -T web php artisan fixtures:seed --force`
   (destructive — see dev/README.md).
4. Credentials and env details: `dev/README.md`
   (backend admin/dev12345 + editor/dev12345; fixture users password dev12345).

## Standing decisions (from the approved spec — do not re-litigate)

- New theme `themes/heroeslounge-next` only; NOTHING under `plugins/rikki/` or
  `themes/HeroesLounge-Theme/` may be modified.
- Design North Star: `docs/superpowers/specs/assets/reference-design.html`;
  authoritative v2 tokens/type scale: `docs/superpowers/specs/assets/dashboard-v2.html`.
- Hand-rolled CSS (no build step), vanilla JS, self-hosted fonts, inline lucide SVGs.
- Buttons/active tabs: dark text `#041020` on storm-blue gradient (decided; contrast-verified).
- Dual homepage: logged-out marketing page / logged-in dashboard, same URL.
- All existing site URLs are frozen.

## Hard-won gotchas (verified this session — trust these)

- October v1 build bug: `v`-prefixed keys in version.yaml loose-compare to 0 →
  plugin/theme silently skipped. Never prefix versions with `v`.
- `Indikator.Content` marketplace plugin is unobtainable (source deleted); dev
  shim lives at `dev/docker/plugins/indikator/content` — swap instructions in dev/README.md.
- RainLab.GoogleAnalytics IS required despite not being in theme.yaml: 10 old-theme
  layouts declare `[googleTracker]`. Removing it 500s the whole site.
- `| page` Twig filter resolves against the ACTIVE theme → for pages not yet
  ported to heroeslounge-next, hardcode literal URL paths (details in plan crash-course).
- Component partial overrides: theme `partials/<alias>/default.htm` dir must match
  the registered alias casing exactly (container FS is case-sensitive; wrong case = silently ignored).
- DivisionTable's real properties are `teamId` + `surroundingEntries`; the
  `maxEntries` seen in old partials is a dead property.
- The notifications pipeline is disabled upstream (Session::put commented out in
  `plugins/rikki/heroeslounge/Plugin.php` boot() ~114-117) — dashboard notifications
  panel legitimately shows its empty state.
- `mmr_bound` column is production drift — created by `plugins/dev/fixtures`'s
  guarded migration, not by rikki migrations.
- Host port 8080 is occupied (EnterpriseDB) — the dev site uses 8090.

## Blocked/waiting

- ~~Real team DB dump~~ **LANDED 2026-07-08** (partial dump imported —
  `hl_test_data_dump_05_2024.sql`). See `docs/superpowers/DB-DUMP-IMPORT.md` for
  the full import record, gaps, and bridge workarounds. Site now runs on real
  data (active season `eu-season-23`). fixtures:seed is superseded (and now
  incompatible with the uncommitted Indikator-shim edits). Still recommended:
  obtain a **complete** dump to drop the workarounds; October Project ID still
  pending for real marketplace plugins.
- Deferred real-data verification unblocked for: homepage/season/division/
  calendar/blog (done). Still blocked on data even with this dump: upcoming-match
  rendering + match-detail draft breakdowns (see DB-DUMP-IMPORT.md caveats).
