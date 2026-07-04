# UI Rework — Progress Tracker

**This file is the single source of truth for execution progress.** Update it at
every task/review transition. (The plan file's inline checkboxes are NOT
maintained — this table is.)

- Spec: `docs/superpowers/specs/2026-07-03-ui-ux-rework-design.md`
- Plan being executed: `docs/superpowers/plans/2026-07-03-ui-rework-phase-1.md`
- Branch: `ui-rework` (fork V-Simos/HeroesLounge, upstream Fabian-Sommer/HeroesLounge)
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

**Next action:** ✅ **PHASE 1 COMPLETE + final whole-theme review passed.**
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

- Real team DB dump + October Project ID: user will obtain later. When available:
  follow "swap in the real dump" in dev/README.md, stop using fixtures:seed,
  re-run visual verification of all pages built so far.
