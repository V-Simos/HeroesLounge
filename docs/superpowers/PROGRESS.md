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

Plan: `docs/superpowers/plans/2026-07-04-phase-2-wave-1-viewing.md`. **✅ COMPLETE**
(subagent-driven, on branch `ui-rework-phase-2` off `ui-rework`). All 9 tasks
through spec + quality/adversarial review; Task 9 finishing pass found **0 re-skin
defects**. Ready for `finishing-a-development-branch`. **Wave 2 (static content) =
a separate later plan, not yet written.**

| Task | Implemented | Spec review | Quality review | Commits |
|---|---|---|---|---|
| 0 — Bracket-render spike (throwaway) | ✅ | ✅ | ✅ (approve-with-nits → fixed) | 160ac0c, 69028c1, 93b79ae |
| 1 — Shared match-card partial | ✅ | ✅ | ✅ (nits fixed + verified) | 275fe2b, 4795a9c |
| 2 — Season overview `/:slug` | ✅ (closed state) | ✅ | ✅ (nits fixed + verified) | 4b59d0f, 19c0222 |
| 3 — Division page `/:slug/:divslug` | ✅ (+ design rework) | ✅ | ✅ design-rework review approved (original nits deferred — see notes) | edbc0bd, 1c4f677, 8e7bdd1, 5ab67d6, cde17ef |
| 4 — Playoff brackets (4a + 4b) | ✅ | ✅ | ✅ (approved; doc nits fixed) | 7897c89, dc01150, ddb3b1c, abdb5b7, 497cf43, d325c13 |
| 5 — Match detail `/match/view/:id` (5a + 5b) | ✅ | ✅ | ✅ (5a+5b review nits fixed + verified) | ae96c3c, 8b8023c, 4486577, 15cea60, c680934 |
| 6 — Calendar `/calendar` | ✅ | ✅ | ✅ (multi-lens review; nits fixed + verified) | 3a5800f, f7ebaf0 |
| 7 — Team page `/team/view/:slug` | ✅ | ✅ | ✅ (adversarial multi-lens; 8 confirmed → fixed + verified) | dca6be1, fba5068, 92b9510, 1421a61 |
| 8 — Season archive `/season/archive` | ✅ | ✅ | ✅ (adversarial multi-lens; 0 findings) | b8906e0 |
| 9 — Wave 1 finishing pass | ✅ | ✅ | ✅ (frozen-source verify; 0 re-skin defects — every finding faithful-to-frozen/dev-artifact/expected-deferred) | _docs-only (this commit)_ |

**Wave 2 (static content) = a separate later plan, not yet written.**

## Task status (Phase 3 — /user auth/account/profile + Events/Guides nav fixes)

**Spec:** `docs/superpowers/specs/2026-07-24-phase-3-user-auth-design.md`
(committed `1d28fc9`; spec-review subagent **Approved first pass** — ~20 claims
spot-checked against the repo, all held; advisory notes folded into the plan:
Task 8 must verify `forceSecure = 1` is what's committed). **Plan:**
`docs/superpowers/plans/2026-07-24-phase-3-user-auth.md`. Branch:
`ui-rework-phase-2` (continues at the merged Wave-1 tip).

| Task | Implemented | Spec review | Quality review | Commits |
|---|---|---|---|---|
| 1 — Forgot password `/user/forgotpassword` | ✅ | ✅ | ✅ (1 fix round; approved) | 2625539, 4deb149, e38d06b |
| 2 — Account: guest half (signin/register) | ✅ | ✅ | ✅ (approved; 1 minor deferred) | 140f2a8 |
| 3 — Account: authed half (tabs/update forms) | ✅ | ✅ | ✅ (1 fix round; approved) | d1ff863, 8787dff |
| 4 — Profile `/user/view/:id` | ✅ | ✅ | ✅ (approved; verifier minors deferred) | c6176ed |
| 5 — Caster schedule `/user/casterschedule` | ✅ | ✅ | ✅ (1 fix round; approved) | 864a3c7, acbf89d |
| 6 — Events archive port + Events link | ✅ | ✅ | ✅ (3 fix rounds; approved) | 6ffa2ad, 83b745b, 60004cd, 69191e6 |
| 7 — Guides static-pages port | ✅ | ✅ | ✅ (approved) | 1c09098 |
| 8 — Phase-3 finishing pass | | | | |

Design facts that drove the spec (verified live 2026-07-24, this session): the
blog is **Indikator.Content** (NOT RainLab.Blog — query the `indikator_content_*`
tables; 446 posts, 28 categories incl. the seeded `events`), so the Events nav
link WORKS in dev; `deactivate_link.htm` is dead code (referenced nowhere, not
ported); RainLab `Account::redirectForceSecure()` 302s plain-http non-AJAX GETs
→ `/user` cannot render in dev with `forceSecure=1` (AJAX exempt; dev
verification flips it temporarily, prod keeps 1); `selectFile.js` contract =
`.fileselect` wrapper + sibling `:text` + `#{fieldName}UploadError` ids +
`avatar`/`banner` input names.

### Notes from Phase 3 Task 1 (forgot password)

- Ported `/user/forgotpassword/:code?` and all four `SlothResetPassword`
  overrides in the required lowercase directory. The restore and reset AJAX
  partial-swap contracts were exercised against the live Docker site.
- Review removed an invented completion link, then added a semantic completion
  `<h1>` so the AJAX-replaced document retains exactly one page heading.
- The reversible user-41 password-reset flow completed successfully and the
  account was restored to `dev12345`; runtime logs remained clean.
- Logged-in redirect verification moves to Task 2 once `/user` exists.
  Browser-only viewport/console coverage remains for the Phase-3 finishing pass.

### Notes from Phase 3 Task 2 (account guest half)

- Ported `/user/:code?`, the lowercase `slothaccount` guest overrides, the
  ruleset copy, responsive two-panel auth layout, and native Code-of-Conduct
  dialog. Invalid registration exercised the real server validation path;
  user-41 email sign-in and the interim authenticated branch both returned 200.
- The frozen interim `SlothAccount::update` partial calls the old theme's
  `country-state/default` partial. A minimal compatibility copy was required
  here so the deliberately deferred Bootstrap update surface renders until
  Task 3 replaces it.
- **Corrected forceSecure finding:** the committed page correctly keeps
  `forceSecure = 1`, but plain HTTP still returns 200 because frozen
  `SlothAccount::onRun()` calls `parent::onRun()` without returning the parent
  redirect response. This is a pre-existing frozen-plugin bug; do not "fix" it
  during the re-skin.
- All temporary user/password/settings mutations were restored. Browser-only
  dialog focus/Esc, viewport overflow, and console checks remain for Task 8.
  Review's non-blocking `autocomplete="username"` suggestion is also parked
  for that finishing pass.

### Notes from Phase 3 Task 3 (account authenticated half)

- Replaced the interim Bootstrap update surface with the established
  lounge.js tabs for General, Media, Social, Game, Applications, and gated
  Notifications. Added lowercase `viewapps` plus the reusable country selector.
- Live reversible checks covered description, links, game, general/password,
  avatar, and banner handlers. New file/timeline rows were removed, original
  attachments were restored, and final user/sloth/file/timeline fingerprints
  matched the pre-write snapshots.
- Review added null-safe `Team unavailable` / `Player unavailable`
  presentation fallbacks and strengthened structural handler/payload, hidden
  panel, link-submit, and upload-DOM verification.
- Browser interaction/visual/console coverage and the project-wide complete
  ARIA tabs pattern remain for Task 8. External Discord, MailChimp, mail, and
  dead-session notification paths remain wiring-only.

### Notes from Phase 3 Task 4 (profile)

- Ported `/user/view/:id` with lowercase `profile` and `slothstatistics`
  overrides, the frozen guest gate, and the established sanctioned 404 for an
  unknown sloth (replacing the frozen 200 empty shell).
- Verified user 41 and a timeline-rich profile, guest and bad-ID states,
  data-blind match/stat empty states, and a temporary revealed-score sloth
  participation. The temporary row and auto-increment were restored exactly.
- The shared `roundMatches` partial now reveals scores only for `type='sloth'`.
  `/team/view/AO` remained byte-identical before/after (same response hash,
  length, grouping tabs, and masked-score count).
- Review approved the complete profile/stat data contract. Task 8 carries two
  verifier-only minors: structurally assert the executable guest gate after
  stripping Twig comments, and cover popularity/map output fields.

### Notes from Phase 3 Task 5 (caster schedule)

- Ported `/user/casterschedule` and the lowercase `casterschedule` override.
  Each runtime `UpcomingMatches` child calls `onRender()` and consumes its
  public `datesToMatches` groups for pending, accepted, and denied queues.
- The frozen `can('cast_matches')` gate comes from ShahiemSeymor Roles—not the
  legacy RainLab `Casters` group. This dump's four Shahiem permission tables
  are empty, so a temporary role/permission assignment was required for live
  verification and then removed with counters restored.
- Review restored the frozen per-row caster request/status fragment. The
  pending queue keeps `onCastRetract`; accepted/denied preserve their original
  status branches, with each handler routed through the actual child alias.
- Guest, non-caster, and all three pivot approval states were verified against
  a temporary future fixture. Match time, pivot, accounts, permission tables,
  and auto-increments were restored exactly.

### Notes from Phase 3 Task 6 (events archive)

- Ported `/events/archive` with the native season-archive accordion pattern and
  a byte-identical copy of the frozen 3-group/14-link menu YAML, including its
  intentionally quirky and protocol-relative URLs.
- Live `/events/archive` and `/blog/category/events` returned 200. The
  production cutover content operation to create/confirm the Indikator
  `events` category is recorded in the hardening list below.
- Review added a visible underline affordance for archive entries, then scoped
  it beneath the Events-only page class so Season Archive `.season-link`
  colors, decoration, and hover behavior remain unchanged.

### Notes from Phase 3 Task 7 (guides)

- Added the Guides-only RainLab.Pages manifest, seven frozen content files, and
  a static layout mirroring default chrome/components around the existing
  blog-prose typography. Every body matches its frozen source after normalizing
  only the `viewBag.layout` value.
- All seven source-governed URLs and the nav/footer guide targets returned 200
  after cache clear; unrelated static pages remain outside the manifest.
- Frozen source front matter governs the First Game URL
  `/guides/scheduling-and-playing-your-first-game`; the plan's verification
  list contains a contradictory `guide/.../frist` typo and no alias was added.
- The frozen First Game body contains six `<h1>` section headings. Task 7
  preserves them byte-verbatim and the layout adds none. Semantic demotion
  requires an explicit future content-migration decision; Task 8 should record,
  not silently rewrite, this known source artifact.

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
  **→ RESOLVED in Task 4:** the real dump has all these types; the frozen
  geometry served every one verified (de16/de8/se16/DivSv1 + group-stage +
  pure-group + reg_open). `de4`/`de6` have no rows in this dump (still
  render-blind) but share the frozen geometry paths that DID verify.
- **Seeder infra note:** `seedPlayoffs()` relaxes then RESTORES its connection
  `sql_mode` (the frozen `createMatches()` inserts match rows without the
  NOT-NULL-no-default `is_played`; prod MySQL runs non-strict). Dev-only, scoped.

### Notes from Task 4 (playoff brackets — IN PROGRESS)

- **DATA CAVEAT RESOLVED — verification path = LIVE real-dump pass (no scoped
  seed).** The Task-0 se8/de8 fixtures were on Season 30 (gone with the real
  dump). But the real May-2024 dump is RICH in playoffs — **every** previously
  "fixture-blind" type now has real data (counts: se8×90, playoffv2×27,
  playoffv3×19, groups×16, se16×16, de8×12, playoffv1×8, se64×7, se32×6,
  playoffv4×4, DivSv1×2, groupsOfFour×2, de8short×1, de16×1). So Task 4 verifies
  against real brackets — a major de-risk vs the spike. `fixtures:seed` is NOT
  used (superseded + Indikator-shim-incompatible).
- **URL RESOLUTION GOTCHA (from PlayoffOverview::init()):** the in-season route
  `/:season-slug/playoff/:playoff-title` resolves the playoff by **TITLE**
  (`$season->playoffs()->where('title', param)`); the standalone
  `/tournament/:playoff-title` resolves by **SLUG** (`Playoff::where('slug',…)`).
  So in-season URLs use the (url-encoded) title, standalone URLs use the slug.
- **Live verification matrix (active season eu-season-23 = season_id 66):**
  - se16 (in-season, by title): `/eu-season-23/playoff/Division%201%20Cup` (id 229, 15 m)
  - playoffv4 (in-season): `/eu-season-23/playoff/Division%202%20Cup` (id 231)
  - de16 (wide → horizontal scroll): `/tournament/nut-cup` (id 27, 30 m)
  - de8 (losers bracket + BYE + finals + repeat teams): `/tournament/eu-offseason-17-18-playoffs` (id 157, 21 m) or `/tournament/loukas-meta-mayhem-groupA` (id 112, 42 m)
  - DivSv1 (special geometry, pure bracket, 0 divisions): `/tournament/eu-division-s-playoffs` (id 38, 8 m)
  - **group stage + knockout (both)**: `/tournament/group-stage-eu-aram-2` (id 51, se16, 8 divisions + 15 knockout m)
  - pure group stage (NO bracket): `/tournament/aram-league-eu-stage-1` (id 33, groupsOfFour, 10 divisions, 0 m)
  - reg_open participants card-deck (18 signed-up teams): `/tournament/nexus-rumble-v` (id 142)
- Front-matter note: old-theme pages used `layout = "plain"`; the plan's VERBATIM
  front-matter uses `layout = "default"` (new theme has no plain layout) — correct.

**Task 4 DONE (4a + 4b).** All bracket types + group stage + participants render
live on the real dump. Spec + quality reviews passed both halves.

**Task 4b DONE** (commits `ddb3b1c` override branches + `divisionView.htm` + pages
+ star icon, `abdb5b7` DivisionTable showScore, `497cf43` CSS, doc `d325c13`;
spec ✅, quality ✅ approved). Extended `partials/PlayoffOverview/default.htm` from
the 4a knockout-only path to the full three-way structure (null / reg_open
participants / group-stage+knockout), matching the frozen component partial
re-skinned. The bracket markup is factored into a Twig `{% macro bracket(SELF) %}`
(explicit component param — macros don't inherit context) so it renders both
standalone (pure knockout) and inside a lounge.js "Knockout Stage" tab panel;
geometry byte-identical to 4a (verified). New `partials/PlayoffOverview/
divisionView.htm` (group pane: showScore standings + round tabs via the Task-3
`match/card` pattern). `partials/DivisionTable/default.htm` gained an ADDITIVE
`{% if __SELF__.showScore %}` branch (# | Team | P | Score | Map ±, from
`getTeamsSortedByScore()` → `team.score`/`team.map_score`; scores wrapped
`.hl-score.is-masked`); the non-showScore homepage/dashboard branch is
byte-unchanged.
- **SPOILER RECONCILIATION DONE (resolves the Task-1/Task-4 flag):** the two
  spoiler languages now share one switch. Both page callbacks additionally toggle
  `body.reveal-spoilers` (which the match-card `.score-masked` uses) alongside the
  bracket/standings `.hl-score.is-masked` JS scheme. Disjoint element sets, no
  double-mask; in-sync across cookie-init / click / lounge.js tab-switch (tabs are
  client-side `[hidden]`, no AJAX re-render). One toggle reveals bracket nodes +
  group standings + round-tab match cards.
- **Nested component access (the key unknown, now proven):** components added in
  `PlayoffOverview::init()` via `addComponent(..., 'roundMatches'/'divisionTable')`
  ARE reachable inside the override as **lowercase-alias page vars**;
  `{% do roundMatches.setProperty(...) %}`/`.onRender()` work — so group rounds use
  the re-skinned `match/card` path, NOT the plugin's Bootstrap partial. (No
  RoundMatches theme override was needed/created.) Standings render via
  `{% component 'divisionTable' id=div.id %}` → the DivisionTable override's
  showScore branch.
- **Two intentional deviations from frozen, now code-commented** (so a future
  faithfulness pass won't revert): divisionView active round uses `i == maxRound`
  (correct latest-round-active) — the frozen partial's `key == maxRound` is a
  latent bug; and the reg_open heading is `<h1>` (frozen/plan said `<h2>`) —
  promoted for one-h1-per-page heading order (all 3 branches mutually exclusive).
- **`assets/img/roles/` does NOT exist** in the new theme (role SVGs live only in
  the frozen old theme, unreachable via `| theme`; no plugin roles dir). Participant
  rosters use a graceful text `.role-tag` badge (e.g. FLE/SUP/TAN) — no broken
  `<img>`, no assets copied in. **TODO (real-icon swap):** drop role SVGs into
  `themes/heroeslounge-next/assets/img/roles/` and swap `.role-tag` for
  `<img … | theme>` when the icons are available.
- **VERIFY-BLIND (real dump):** the captain **signup form** (reg_open branch)
  needs a logged-in captain with an eligible, un-signed-up team — no such fixture,
  so only the anonymous path (Participants list, no form) was verified live. Markup
  + frozen `onTeamSignup` handler/`playoff_id`/`team_id` field names are byte-exact.
- **Verified LIVE** (real May-2024 dump): group+knockout `/tournament/
  group-stage-eu-aram-2` (9 Stage tabs, Knockout active, 8 score standings masked,
  60 round cards, 16 nodes, tab-switch works); pure group `/tournament/
  aram-league-eu-stage-1` (10 tabs, first active, no bracket, mobile OK); reg_open
  `/tournament/nexus-rumble-v` (18 participant panels, captain stars, no form for
  anon); knockout regressions `/tournament/nut-cup` (de16, restyle survives, no
  heroeslounge.css clobber), `/tournament/eu-offseason-17-18-playoffs` (de8, BYE +
  bracket-losers), in-season `/eu-season-23/playoff/Division%201%20Cup`. Console
  clean bar the documented logo-404 dev-data artifact; `storage/logs` clean.
- **Optional DRY left as-is** (non-blocking, faithful-re-skin mirrors): the two
  round-render blocks in divisionView (`minRound<maxRound` vs single) duplicate the
  card-render body; `.tr-score` column magic numbers duplicated desktop/mobile.

**Task 4a DONE** (commits `7897c89` + doc nit `dc01150`; spec ✅, quality ✅
approved). Built `pages/season/playoff.htm` (by-title, callback
`showHideSpoilersSeasonPlayoff`) + `pages/playoff/view.htm` (by-slug, callback
`showHideSpoilersPlayoffView`). The Task-0 spike had already shipped the override
geometry + `.hl-bracket` CSS + scroll wrapper UNCHANGED, so 4a = the two page
shells + the ported spoiler callback (frozen repeat-team + BYE logic retargeted
from `.result/.name/.logo/spoiler/notext` onto `.hl-score/.hl-name/.hl-logo/
is-masked`; `span.f100` + results-table `td.score` hooks dropped as non-existent
in the new node markup; `bracket-losers` hook preserved). BYE score-reveal
retargeted `elem.parent().parent().find('.result')` → `elem.closest('.hl-node-
card').find('.hl-score')` (class-based, more robust than the positional hop).
Verified LIVE: de16 `/tournament/nut-cup` (0.00px connector alignment, 30 nodes),
de8 `/tournament/eu-offseason-17-18-playoffs` (BYE/losers hooks), se16 in-season
by title, null → literal not-found, spoiler toggle masks/reveals both directions.
**Dev-data artifact (not a bug):** the dump ships no team-logo uploads → shields
404 and the plugin's ResizeSensor/ElementQueries re-request in a ~5s loop; abort
image requests when driving Playwright. **Two callbacks are near-identical (only
the fn name differs) — accepted as-is** (page-scoped callbacks, faithful mirror
of the frozen two-copy theme; revisit only if a 3rd consumer needs the walk).

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

### Notes from Task 5 (match detail `/match/view/:id` — DONE)

**Task 5 DONE (5a + 5b).** Adversarial multi-lens reviews (spec + fidelity +
quality + a11y) passed both halves; all findings were minor nits, fixed + verified.
Commits: `ae96c3c` (image-asset port), `8b8023c` (5a), `4486577` (5a nits),
`15cea60` (5b), `c680934` (5b nits).

- **Image assets ported (`ae96c3c`):** `heroes/` (90), `maps/` (17), `talents/`
  (2079), `roles/` (8 SVGs) copied verbatim from the old theme into
  `assets/img/` (plugin-derived filenames resolve unchanged via `| theme`).
  Separate prep commit so the markup diffs stayed reviewable. (The old
  `assets/img/icons/` stat SVGs were copied then removed — 5b used lucide `swords`
  + text headers instead, leaving them dead.)

- **5a — page + header + rosters + scheduled (`8b8023c`, nits `4486577`).**
  `pages/match/view.htm` attaches `[ViewMatch]`, drives `{% do ViewMatch.onRender()
  %}`, reads `ViewMatch.match`. **onEnd 404 GOTCHA:** ViewMatch is onRender-ONLY
  (no onRun) → `ViewMatch.match` is NOT populated during the page `onEnd()` (onEnd
  runs before Twig). The blog/post `$this->post` pattern does NOT transfer — onEnd
  does its OWN `\Rikki\Heroeslounge\Models\Match::find(param('id'))` for the 404 +
  title. `partials/match/header.htm` re-skins the frozen 4-branch hierarchy
  (season/round + division-or-playoff + Cantor context) as eyebrow + ONE `<h1>` +
  sub-labels; the subject blocks are chained with `elseif` so at most one `<h1>`
  fires, every `season.slug` deref is null-guarded, and a `playoffHref(p)` macro
  centralises in-season (`/:season/playoff/:title`) vs standalone
  (`/tournament/:slug`) — also fixing the frozen's latent standalone-group-stage
  broken link. `decoded_playoff_position` comes from the component (Cantor decode
  NOT re-ported). Casters = `getAcceptedCasters` + per-channel `site/icon` twitch
  links (aria-label disambiguated per channel). `partials/match/team-roster.htm`
  (games-empty branch): `.p` panels, `team/shield size='lg'`, role SVG chips
  (null-role skipped), **neutral-when-undecided** winner/loss (deviation from the
  frozen paint-every-non-winner-danger; matches `match/card`'s `decided` gate).

- **5b — game tabs + per-game statistics (`15cea60`, nits `c680934`).** The
  games-present branch renders lounge.js game tabs; each panel invokes `{% component
  'gameStatistic' game_id=game.id lazy=(not loop.first) %}`. The **override at
  `partials/gameStatistic/default.htm`** (case-sensitive alias `gameStatistic`,
  registered in `ViewMatch::init()` — PROVEN to resolve over the plugin's
  Bootstrap+DataTables partial) is a thin delegate reading `__SELF__.game`/`.lazy`
  → `partials/match/game-stats.htm` (278-line faithful re-skin). **DataTables
  DROPPED entirely** — its only job was sorting rows by the hidden TGroup column,
  but `game.getTeamsGrouped` already returns rows team-grouped, so the sort was
  redundant. Bootstrap collapse → native `<details>/<summary>`; Bootstrap tables →
  semantic `<table class="gstat-table">` (sr-only captions + robust `<th
  scope="row">` names); every dynamic hero/map/talent `<img>` has an `onerror` →
  neutral placeholder. Empty-branch copy clarified ("No statistics recorded for
  this game." — the frozen "No Replay File found!" was misleading, not a
  must-preserve string). Fidelity confirmed binding-by-binding (ban
  First+Second-grouped/Third-separate, talent pad-to-7, `number_format` on the 4
  damage/heal/exp cols only, team-accent rows).

- **⚠ ENVIRONMENT FIX (dev DB, not code) — MUST survive re-import:** 5b exposed
  that the partial dump's recreated `rikki_heroeslounge_gameparticipation` was
  missing `team_id` + 9 stat columns (recreation ran only the `create` migration,
  not the `_update_*` ones). GameStatistics' `byTeam` scope (`orderBy('team_id')`)
  eager-loads for any game with a `winner_id`, so **every with-games match 500'd**
  before any markup (would 500 the OLD theme too). Fixed via `ALTER TABLE` to
  production schema — **recorded + reproducible in `DB-DUMP-IMPORT.md` (§A + step
  5)**. Table stays 0-rows (stats still fixture-blind); page no longer crashes.

- **FIXTURE-BLIND on this dump (verify with a complete dump):** the ENTIRE
  populated per-game stats view (teams header, first-pick, hero picks, bans, map +
  replay download, kills/duration/level/winner summary, stats table, talents
  table) — `gameparticipation`/`_talent` = 0 rows, so every game renders the empty
  branch; replays (36K files) + talents live inside the guard, also blind. Also
  blind: populated pre-game ROSTERS — no-games matches have 0 `team_match` rows
  (teams attach alongside games), so the roster always hits the bye-guard. Fidelity
  of the blind markup rests on the binding-by-binding adversarial review (which
  substituted for the missing live test).

- **Verified LIVE (real May-2024 dump):** header hierarchy + links on division
  (`/match/view/1061`) and playoff (`/match/view/21642`, Cantor "Winner Bracket /
  Round 1 - Match 3" + casters + twitch); scheduled TZ time vs red "not scheduled
  yet" (`/match/view/22463`); exactly one `<h1>` + well-formed hrefs on every
  shape; game tabs render + switch (3-game `22383`, 18-game `1061`) each panel =
  themed empty state + "Winner: X"; `/match/view/999999` → 404 + themed not-found;
  console clean; `storage/logs` CLEAN (truncate + reload all shapes → empty).

- **DEFERRED (theme-wide a11y sweep, NOT Task-5-local):** the shared lounge.js
  `[data-tabs]` pattern lacks (a) `aria-labelledby` linking each tabpanel to its
  tab and (b) roving-tabindex arrow-key nav for `role=tablist` (APG) — both affect
  rounds/playoff/game tabs identically; fix once in the shared handler. Accepted
  minor: the Hero+Player row-head cell duplicates across the stats + talents
  tables (a `playerRowHead` macro would DRY it — left to avoid refactoring
  fixture-blind, fidelity-verified markup).

- **Plugin residue (harmless, frozen):** `GameStatistics::init()` still
  `addJs/addCss`'s DataTables assets on every game render; they load unused (no
  init runs) — same accepted-residue class as Task 4's ResizeSensor.

### Notes from Task 6 (calendar `/calendar` — DONE)

**Task 6 DONE.** Adversarial multi-lens review (spec + fidelity + quality + a11y,
each finding adversarially verified) passed — the ONLY confirmed finding was
downgraded to a doc nit; everything else was faithful-to-frozen (mandated),
objectively-better (kept), or deferred theme-wide a11y. Commits: `3a5800f`
(calendar page), `f7ebaf0` (review-nit doc/dead-CSS follow-up).

- **Shipped:** `pages/calendar.htm` + `partials/calendar/{event-list,caster-requests}.htm`
  + `partials/site/icon.htm` (added `mic`/`calendar-plus`/`calendar-x` lucide
  icons — that partial is in the NEW theme, editable) + `assets/css/pages.css`
  (`.cal-*` block, token-driven, responsive @760px, reduced-motion). Front-matter
  VERBATIM from the frozen page (dropped its vestigial `onStart(){ Match::find(1) }`
  — the `data` var was unused; the themed page needs no PHP).

- **Card-reuse decision (HAND-ROLLED the row, did NOT compose `match/card`).** The
  fixture card is a self-contained chamfered `<article>` with its own `.match-top`
  (division/date/vod) row; nesting it in a per-date event row reads as a card-in-row,
  and the calendar row needs several things the card deliberately omits (kickoff-
  time-only, playoff/division precedence label+link, per-channel Twitch links, the
  `[REGION]` tag, the interactive caster column). So the row reuses the shared
  design *vocabulary* (`team/shield` + `.side`/`.tname`, `.mday`-style day header)
  but owns its shape. All 4 review lenses concurred (faithful + defensible).

- **Wiring facts (record for T7 which reuses UpcomingMatches):**
  - `UpcomingMatches` type=`all` is onRender-only; the PAGE calls
    `{% do UpcomingMatches.onRender() %}` once, and the attached component is
    readable by alias inside partials rendered within the page (same as the T3
    division sidebar). `datesToMatches` is grouped `'d-M-y'` AFTER
    `orderBy('wbp','asc')` → **iterate AS-IS; never re-sort the day-first keys.**
  - Caster `data-request` alias is **HARDCODED** `UpcomingMatches::onCastRequest` /
    `onCastRetract` (in a theme partial `__SELF__` is the PARTIAL, not the component,
    so the old `{{ __SELF__ }}` prefix can't be used); container id
    `divCasterRequests{{ match.id }}` kept **verbatim** (the plugin's AJAX handlers
    target it). Caster gate replicated byte-exact incl. sub-conditions; `user`
    passed in as `user=UpcomingMatches.user`.
  - Literal frozen URLs throughout (playoff precedence division.playoff > playoff >
    division; in-season-by-title vs standalone-by-slug; `|url_encode` on titles).

- **Two INTENTIONAL deviations from frozen, now code-commented** (so a future
  faithfulness pass won't revert): branch-3 label gated `elseif match.division`
  (the frozen unconditional `else` emitted an empty href-less `<a>` for an orphaned
  match with no division AND no playoff — a nameless dead anchor; suppressing it is
  better a11y and only differs for a data anomaly since type=`all` eager-loads
  `division`); and `<time datetime>` uses valid ISO `Y-m-d` (the frozen read `.wbp`
  off the grouped *Collection* — not a real timestamp — with the HTML-invalid
  `m/d/Y`).

- **KNOWN LIMITATION (un-skinnable, frozen):** the caster apply/retract AJAX
  response re-renders the plugin's Bootstrap `@casterRequests` fragment (un-skinned)
  into `#divCasterRequests{id}` — only the INITIAL render is themed. (The `renderPartial('@casterRequests')` could in principle be theme-overridden at
  `partials/UpcomingMatches/casterRequests.htm`, but that was NOT attempted — the
  plan accepts the un-skinned swap; revisit if the interactive caster path is ever
  productionized.)

- **Verified LIVE:** `/calendar` HTTP 200, guest empty-state renders, console clean
  (bar the known logo-404 dev-data artifact), `storage/logs` CLEAN after reload.
  The real May-2024 dump's matches ALL predate the container clock (today
  2026-07-10), so the 100-day window is **naturally EMPTY** — populated verification
  was done by temporarily forcing 4 representative matches into the window (one per
  precedence branch → correct URLs: `/season-4/playoff/Championship`,
  `/tournament/nut-cup`, `/season-5/playoff/Cup`, `[EU] /euseason-14/division-4` +
  twitch; VS→`/match/view/:id`; accepted-caster mic links), plus a TZ test
  (Europe/Athens vs America/New_York) that shifted a row time AND moved a
  date-group boundary — **all DB values restored afterward** (data-only, no code).

- **FIXTURE-BLIND on this dump:** (1) the positive `can('cast_matches')` apply/retract
  branch — no caster user available; the gate renders nothing for a guest/non-caster
  without error (all that could be confirmed). (2) A *naturally* populated calendar
  (all live matches are past-dated) — populated rendering rests on the forced-match
  live test + the binding-by-binding review.

- **PERF (flagged, NOT a defect):** with ~598 matches force-loaded, `/calendar`
  server-render took ~66–99s — the frozen type=`all` N+1 (`division.playoff`,
  `division.season`, `playoff.season`, `teams.region` are NOT eager-loaded; the
  reused `team/shield` adds one `smallLogo` lazy-load per team). Inherited verbatim
  from the frozen plugin + old theme (the re-skin adds no overhead) — do NOT optimize
  (plugins frozen). Same class as the Task-8 "UpcomingMatches type=all hydrates every
  unplayed match" pre-production hardening note; real production windows differ from
  this synthetic worst case.

- **DEFERRED (theme-wide a11y, NOT calendar-local):** (a) the caster apply/retract
  controls are `<a role="button">` with NO href → not keyboard-focusable/operable
  (FAITHFUL to the frozen partial; the re-skin *added* the `aria-label`s the frozen
  lacked) — same bucket as the Task-5 deferred `[data-tabs]` a11y sweep; fix once
  with `tabindex`+Enter/Space handler when the interactive caster path is exercised.
  (b) a chamfered CONTAINER's `clip-path` (`.cal-group`, like every `.chamfer` panel
  — `.divside-panel`, `.p.chamfer`) can clip a descendant link's positive-offset
  focus ring at the 14px corners; a **pre-existing theme-wide characteristic**, not a
  calendar regression (adversarially refuted as material) — the focus-affordance
  invariant only covers elements that THEMSELVES carry `.chamfer`/clip-path. Revisit
  theme-wide if it ever bites.

### Notes from Task 7 (team page `/team/view/:slug` — DONE)

**Task 7 DONE (3 build commits + 1 review-fix commit).** Adversarial multi-lens
review (spec + fidelity + quality + a11y, each finding independently verified):
10 raw findings → 8 confirmed (2 critical [same root cause], 2 minor, 4 nit) + 2
refuted (sanctioned deviations). All fixed + verified live. Commits: `dca6be1`
(banner+roster+sidebar), `fba5068` (matches+timeline), `92b9510` (statistics),
`1421a61` (review fixes).

- **Shipped:** `pages/team/view.htm` (onEnd 404 reads
  `$this->page->components['ViewTeam']->team` — ViewTeam populates `.team` in
  `init()` every request, so onEnd can 404 directly; SIMPLER than ViewMatch's
  onRender-only 404 dance in Task 5) + `partials/ViewTeam/default.htm` (full-bleed
  banner + lounge.js tabs + sidebar) + `partials/team/{roster,social}.htm` + the
  SIX camelCase component overrides + ported `assets/img/{battlenet,discord}.svg`
  + a `globe` lucide icon + `.team-*` CSS.

- **⚠ CRITICAL CROSS-TASK LEARNING — component-override alias resolution is
  strtolower-FIRST.** October's `ComponentPartial::loadOverrideCached`
  (modules/cms) probes `partials/strtolower(alias)/default.htm` BEFORE
  `partials/<exact-alias>/default.htm`. The DivisionTable override dir was
  `partials/DivisionTable/` (CamelCase) — that matches the `[DivisionTable]` PAGE
  alias via the exact probe, but NOT the lowercase RUNTIME alias `divisionTable`
  that BOTH ViewTeam (Task 7) AND PlayoffOverview's group-stage (Task 4)
  `addComponent()`. On case-sensitive prod Linux neither probe matched → the
  plugin's un-skinned Bootstrap table leaked into the team-sidebar Standings AND
  the playoff group-stage standings. **Dev's Docker-Desktop bind mount is
  case-INSENSITIVE even inside the Linux container, so this was 100% invisible in
  dev** (live rendering does NOT prove casing — only `git ls-files` + the October
  source do). FIX (`1421a61`): renamed `partials/DivisionTable/` → all-lowercase
  `partials/divisiontable/`, which the strtolower-first probe matches for EVERY
  alias casing on any FS — one dir, and it closes the Task-4 latent leak for free.
  **RULE for all future component overrides: name the override dir ALL-LOWERCASE
  (strtolower is always probed first); a CamelCase dir only works when the
  invoking alias is byte-identical, which silently breaks any lowercase
  runtime-alias consumer on prod Linux. Do NOT rename `divisiontable` back.**
  Verified live post-fix: home (5 themed widgets), team sidebar, division page,
  playoff group-stage all themed, 0 `table-striped`, logs clean of errors.

- **Guest-vs-auth roster split PRESERVED:** page declares NO `[session]` (inherits
  `security="all"`); guests see names + captain star + role only; the `{% if user
  %}` block adds battle_tag/discord/heroesprofile deep-link + per-player socials.
  Guest-safe: the upcoming override NEVER derefs `user.username` (the frozen
  jumbotron empty-state crash for guests is avoided). Authed path exercised live
  by setting password `dev12345` on user id 41 (DOF captain) via tinker (data-only
  dev change, noted — survives).

- **camelCase overrides:** five resolve via the EXACT probe as-is (`recentResults`,
  `upcomingMatches`, `roundMatches`, `timeLine` [capital L], `teamStatistics`);
  only `divisionTable` needed the lowercase-dir fix above. `upcomingMatches` has
  showCasters=false/showName=false → NO caster column (simpler than the calendar).
  `roundMatches` type='team' round=null → `matches` GROUPED by division/playoff
  `longTitle` (NOT per-round) → re-authored as one lounge.js tab per group; shows
  the team's FULL match history (DOF = 41 groups / 218 cards). The `timeLine`
  per-type switch was extracted to a shared `partials/division/timeline-entries.htm`
  (also consumed by the Task-3 division sidebar — one source of truth; de-risks the
  Phase-3 `/user/view` URL change).

- **Statistics tab (scope-cut per plan):** static themed hero + map tables (NO
  DataTables, NO Bootstrap collapse); the AJAX `onSeasonChange` season-select +
  client-side sorting are DEFERRED. PARTIALLY fixture-populated: hero BANS derive
  from the `games` table (49k rows) so the tables render, but PICKS/winrate show
  `-`/empty because `gameparticipation` = 0 rows (same fixture-blind class as Task
  5). Empty-state ("No statistics yet", now an `<h2>` for heading order) shows for
  teams with 0 participated seasons (e.g. `testus`).

- **Two sanctioned deviations (refuted as findings — documented + objectively
  better):** roundMatches null-group tab labels "Other" not the frozen "Playoffs"
  (a real playoff match is keyed by `playoff.longTitle` and never hits the null
  branch, so "Playoffs" was a frozen misnomer); upcomingMatches renders team
  titles despite showName=false (a shields-only text row is ambiguous in the
  sidebar).

- **FIXTURE-BLIND / deferred:** populated hero-pick/winrate stats
  (`gameparticipation`=0); statistics AJAX season-change + sorting (deferred);
  `/user/view/:id` links emit frozen literals but 404 under the new theme
  (Phase-3, expected). Team-logo uploads absent in the dump → shield 404 +
  ResizeSensor re-request loop (known dev-data artifact, not a bug).

- **Frozen INFO-log noise (pre-existing, NOT a Task-7 defect):** `Division.php:190`
  `Log::info` serializes full standings on EVERY DivisionTable render, so the
  group-stage page (many tables) writes multi-hundred-KB INFO lines to
  `system.log`. Already tracked under "pre-production hardening" above; `system.log`
  is clean of ERROR/exception/Twig lines.

### Notes from Task 8 (season archive `/season/archive` — DONE)

**Task 8 DONE (1 build commit).** The lowest-complexity Wave-1 task ("free-rider").
Adversarial multi-lens review (spec + fidelity + quality + a11y, each finding
refutation-tested via a background review workflow): **0 findings raised** — clean
first pass. Commit: `b8906e0` (Fable implementer).

- **Shipped:** `pages/season/archive.htm` (pure page `onStart()` data binding — NO
  components, NO new JS) + a `.arch-*` block in `assets/css/pages.css`. The old
  Bootstrap `#accordion`/`.card`/`.collapse` is re-skinned as native
  `<details>/<summary>` (keyboard-operable with zero JS). Each `<details>` body
  reuses the FROZEN shared `partials/season/overview.htm` (built in Task 2 —
  untouched) via `{% partial 'season/overview' season=season %}`, so all
  division/playoff link + label-by-`season.type` logic is inherited, not
  re-authored.
- **Query byte-VERBATIM** from the frozen old page:
  `Season::where('type',1)->with('divisions','playoffs')->where('is_active',0)
  ->orderBy('created_at','desc')->get()->groupBy('region_id')` (FQCN
  `\Rikki\Heroeslounge\Models\Season`). Group KEY is the int `region_id`, so the
  region `<h2>` reads `season.region.title` off the group's first season
  (`loop.first` trick, same as the old page). `region` is NOT eager-loaded → lazy
  per season (~27, bounded — faithful, not "fixed").
- **⚠ SOFT-DELETE SUBTLETY (spec vs live).** The plan's Task-8 notes anticipated
  **28** archived seasons incl. season id 30 `[on Battlefield of Eternity]` (slug
  `BoE`) as the "0-divisions/0-playoffs → empty-branch" edge case. Live render is
  **27**: season 30 has `deleted_at` set and the Season model uses the `SoftDelete`
  trait, so Eloquent's global scope excludes it — **the OLD page renders 27 too
  (faithful, not a bug).** Consequence: the shared partial's "Nothing scheduled
  yet." empty branch is NOT exercised by live data (no surviving season has neither
  divisions nor playoffs); its guard lives in the frozen/already-approved shared
  partial, and the archive adds `.arch-body > .season-empty { padding: 0 20px }`
  so it aligns to the summary gutter IF it ever renders. (Season 62 similarly shows
  7 divisions not 8 — one division soft-deleted; render matches live relations
  exactly, 7+7=14 links.)
- **CSS:** `.arch-summary` is a 52px panel row on the existing `.season-link`
  rhythm (Chakra Petch, chevron) and deliberately carries **NO `clip-path`/
  `.chamfer`**, so the global `:focus-visible` storm ring applies as-is — no
  focus-affordance-list entry needed (same call as `.season-link`). Native marker
  suppressed (`list-style:none` + `::-webkit-details-marker`), the shared
  `chevron-right` icon (emitted `aria-hidden="true"`) rotates 90° on `[open]`.
  Follows the pages.css per-section convention: a LOCAL `@media
  (prefers-reduced-motion: reduce)` neutralizes the two transitions right after the
  block (pages.css has no single trailing reduced-motion block).
- **Region order faithful:** `created_at desc` + `groupBy('region_id')` yields
  deterministic **EU → NA** on this data (matches the old page's insertion-order
  loop); no explicit region sort added (would deviate from the frozen query/loop).
- **Verified LIVE** (real May-2024 dump; independently re-checked by the
  controller, not just the implementer's report): `/season/archive` HTTP 200; exactly
  one `<h1>`; region `<h2>`s `EU`,`NA`; **27** `<details>/<summary>`; **0** matches
  for `data-toggle`/`class="card"`/`class="collapse"`/`id="accordion"`;
  `url_encode` intact on playoff titles (`/eu-offseason-23-24/playoff/Offseason%2023-24`);
  `storage/logs` clean of ERROR/Twig lines (only the known Division.php INFO noise).
- **NOT fixture-blind** (unlike most Wave-1 pages): the real dump has 27 archived
  seasons across EU+NA, so this page rendered fully populated live. Only the
  all-empty-sections `<details>` body (empty-branch) is unexercised, per the
  soft-delete note above.

### Notes from Task 9 (Wave 1 finishing pass — DONE)

**Task 9 DONE.** Cross-cutting QA/regression sweep across every Wave-1 page
(season overview, division, playoff brackets [knockout / in-season-by-title /
group-stage+knockout / reg_open participants], match detail, calendar, team,
season archive) at 360/768/1200px — **zero re-skin defects**. Every candidate
finding was adjudicated against the frozen source + old theme as
faithful-to-frozen, a dev-data artifact, or expected deferred-state behavior
(the load-bearing `longTitle` claim was re-verified directly, which corrected an
initial mechanism error — see below). **No code changes — this is a docs-only
commit.**

- **Method:** controller-driven Playwright sweep. Two background static audits
  (CSS focus-affordance completeness + reduced-motion ordering; cross-link
  empty-href + literal-vs-old-theme) — both **0 findings**. Per-page live checks:
  an objective JS diagnostic (page-level horizontal overflow + offender elements,
  excluding intentional scroll containers; `<h1>` count; empty/`#` hrefs) at each
  viewport, console(error) capture, targeted 360px screenshots. A final
  adversarial-verification agent re-challenged all adjudications.

- **⚠ URL LIST REFRESHED (fixture-era → real dump):** the earlier Task-2/3
  verification URLs (`/season-30`, `/season-30/division-1`) are **STALE** —
  season-30 does NOT exist in the real May-2024 dump, so those now exercise the
  themed not-found states (division → "Unknown division" 200; season → not-found,
  both clean). Active **eu-season-23 is CLOSED (is_active=0)** with
  `division-1..5`. Canonical real-dump Wave-1 URLs: season `/eu-season-23`;
  division `/eu-season-23/division-1`; brackets `/tournament/nut-cup` (de16, wide
  → horizontal scroll) + `/eu-season-23/playoff/Division%201%20Cup` (se16
  in-season) + `/tournament/group-stage-eu-aram-2` (group+knockout, 9 tabs) +
  `/tournament/nexus-rumble-v` (reg_open, 18 participants); match `/match/view/1061`
  (18 games) + `/match/view/21642` (playoff, 5 games); `/calendar`; team
  `/team/view/DOF` (heavy, 218 matches) or `/team/view/AO` (light, 25); `/season/archive`.

- **Step 1 responsive (360/768/1200): PASS, all pages.** No page-level horizontal
  overflow at any viewport (bracket width correctly contained in
  `.hl-bracket-scroll{overflow:auto}` — scrollWidth 1892 vs clientWidth 297 at
  360px, page overflow −15px). Division 2-col → 1-col; standings `.tr` name column
  ellipsis-truncates at 360px (no hard-wrap breakage); team banner + roster stack;
  match game-tab grid wraps; exactly one `<h1>` per page.

- **Step 2 keyboard/focus: PASS.** Static audit: every focusable `.chamfer`/
  clip-path Wave-1 element (`.hl-node-card`, `.tab`, `.gstat-toggle`,
  `.gstat-replay`, `.btn`, `.select`, `.socials a`, `.rmate-main`) is in the
  inset-ring affordance list (**0 missing**); non-clipped links (`.season-link`,
  `.arch-summary`, standings `.tteam a`, `.cal-*`) correctly rely on the global
  outset ring. Live: keyboard-Tab to a non-clipped standings link → 2px solid
  storm ring at **+3px OUTSET** (`:focus-visible` true); to a chamfered `.tab` →
  2px solid at **−4px INSET** (not clipped). Both render.

- **Step 3 prefers-reduced-motion: PASS.** Static: the
  `@media (prefers-reduced-motion: reduce)` block is **LAST** in components.css
  (starts ~L800, nothing after) and neutralizes ticker/dot/spoiler/bracket/
  electric-row motion. Live (`emulateMedia` reduce): `.score-masked` transition
  0.2s→**0s**, spoiler-switch 0.18s→**0s** (no-preference keeps them active).
  pages.css keeps its per-section reduced-motion convention (separate, expected).

- **Step 4 timezone correctness: PASS.** Viewer tz lives in the October SESSION
  (`Session::get('timezone')`; set via `SetTimezone::onTimezoneDetection` AJAX →
  `Session::put`). Match `/match/view/1061` (Dec 2017): Athens **"22:56"** → New
  York **"3:56 pm"** — DST-aware (22:56 EET = 20:56 UTC = 15:56 EST) and the
  12/24-hour format switches per `TimezoneHelper::getTimeFormatString()`
  (America/* → 'g:i a'). Calendar date-group boundary: forced 2 matches
  (21691/21692) to boundary-crossing UTC times **and nulled `winner_id`** (the
  `type=all` filter is `winner_id IS NULL`, NOT is_played — UpcomingMatches.php:87)
  → under NY they grouped **Fri 24 / Sat 25 Jul**, under Athens **Sat 25 / Sun 26
  Jul**: both the **time AND the date-group boundary shifted** (grouping applies
  session tz via `Carbon::parse(wbp)->setTimezone($tz)->format('d-M-y')`,
  UpcomingMatches.php:103). The calendar also prints an "All times in <tz>
  <offset>" header. **All DB values restored** (wbp/is_played/winner_id;
  data-only, no code).

- **Step 5 console + log hygiene: PASS.** `storage/logs/system.log` after the full
  sweep: 7 lines, all `.INFO` (the known Division.php standings-serialization
  noise) — **0 ERROR/exception/Twig/Fatal**. Console per page: only the documented
  dev-data artifacts (team-logo `storage/app/uploads/*.png` 404s; the ssbuttons
  CSS 404 below).

- **Step 6 cross-link resolution: PASS.** Static: no empty/`#` hrefs; every
  optional-relation href guarded; all deferred literals match old-theme `url=`.
  Live: all 8 Wave-1 targets **200**; `/user/view/1` **404**, `/team/match/AO`
  **404** (3-segment, expected until Phase 3). **`/team/create` returns 200**
  rendering the themed division "Unknown division" page — the 2-segment URL is
  caught by the division route `/:slug/:divslug` because the new theme has no
  `pages/team/create.htm` yet (a graceful themed 200, not a broken link; Phase 3
  reclaims it when team/create is ported). It is the ONLY 2-segment deferred
  literal; all other deferred/cross-links are Wave-1 (200) or 3+ segments (404).

**Adjudicated NON-defects (faithful-to-frozen / dev-artifact — do NOT "fix";
verified against frozen source + old theme):**
- **Playoff `<title>` reads doubled on `nexus-rumble-v`** (`Nexus Rumble V -
  Nexus Rumble V`): frozen `PlayoffOverview::init()` (PlayoffOverview.php:53) sets
  `$this->page->title = $this->playoff->longTitle`. The `Playoff::longTitle`
  accessor (Playoff.php:41-47) is `season ? season.title.' - '.title : title` — it
  does NOT double for a truly seasonless playoff. This tournament just HAS a season
  (id 45) whose title `"Nexus Rumble  V"` ≈ the playoff title `"Nexus Rumble V"`, so
  the season-present branch yields the near-identical pair. So it's a DATA artifact
  rendered by the frozen accessor, NOT a re-skin quirk; the old theme's
  `[PlayoffOverview]` page shows the identical title, and the new
  `pages/playoff/view.htm` adds no title logic of its own. In-season titles are
  correct (`[EU] Season 23 - Division 1 Cup`).
- **Match scheduled-time offset LABEL uses the "now" offset** (`+03:00` on a Dec
  date): frozen `TimezoneHelper::getTimezoneOffset()` =
  `(new DateTime('now', tz))->format('P')`; the frozen `viewmatch/default.htm:83-84`
  renders the identical `({{ timezone }} {{ timezoneOffset }})`. The displayed TIME
  is DST-correct (`|date(datetimeFormat, timezone)`) and shifts with viewer TZ —
  only the appended label is "now"-offset. Faithful; `partials/match/header.htm`
  mirrors the frozen partial.
- **ssbuttons CSS 404 on the team page** (`/plugins/martin/ssbuttons/.../
  social-sharing-nb.css`): frozen `ViewTeam.php:30` unconditionally `addCss(...)`s
  it; the real `martin.ssbuttons` marketplace plugin is absent (only the
  `plugins/dev/fixtures` shim). The new theme references ssbuttons nowhere;
  ViewTeam drives it for BOTH themes → the old team page 404s it identically. Same
  class as the Indikator shim / logo-404s.
- **Page `<title>` "season"/"Division" on not-found URLs:** the dynamic title works
  on real pages (`[EU] Season 23`, `Division 1`); the static front-matter title
  only surfaces on the not-found path (no real record). Not a bug.

**Fixture-blind / deferred (unchanged from Tasks 1–8; for a COMPLETE dump / later
phase):** per-game statistics + populated pre-game rosters (gameparticipation = 0
rows); real replay files; team hero-pick/winrate stats; latin-ext roster glyphs;
team-logo uploads (→ shield 404s + ResizeSensor loop); bracket types with no rows
in this dump (de4/de6, share the verified frozen geometry). Deferred sub-items:
match-detail sortable stat tables; team Statistics AJAX season-change + sorting;
calendar caster-request AJAX-fragment skin (only initial render themed); season
reg_open ParticipationOverview override; theme-wide `[data-tabs]` ARIA/
roving-tabindex a11y sweep + caster apply/retract keyboard operability; reg_open
captain **signup form** (no eligible-captain fixture — anon Participants path only).

**PERF (frozen N+1, pre-production hardening — NOT a re-skin defect):** the **team
page is the worst offender** — `/team/view/DOF` (218 matches via `roundMatches`
type='team', unbounded by count) server-renders in **~72s** (HTTP 200, correct
markup). Same frozen-N+1 class as the calendar type=all note; add to the
plugin-side hardening list. The re-skin adds no overhead; a lighter team
(`/team/view/AO`, 25 matches) was used for the visual/responsive check (72s
exceeds Playwright's 60s nav cap).

## Live-data seed (2026-07-23/24) — fixtures:live-data

Dev-only artisan command (`plugins/dev/fixtures/console/SeedLiveData.php`) that
generates live-looking data **on top of the imported prod dump** — additive and
scoped to the active seasons (eu-season-30 divisions 757–761 rounds 1–3, NMMR3
division 762 round 1) plus `dev-` slug blog content (events category + posts).
Result: 117 matches (96 played — 72 decided / 24 draws — 11 future-scheduled,
10 unscheduled, 6 BYEs), populated standings/rounds/calendar/match/team pages,
homepage widgets, and a working `/blog/category/events`. Verified by a 13-URL
HTTP sweep (all 200, all content signals present, 0 ERROR/exception/Fatal log
lines); per-game stats remain blind (gameparticipation = 0 rows, dump
limitation). Runbook: `dev/README.md` § "Live-data seed"; audit deltas:
`docs/superpowers/KNOWN-ISSUES.md` 2026-07-24 update.

**Commits:** 29f23d7 (skeleton), 169b8f1 (generation), 220896d (orphan purge),
1b96190 (quality fixes), cc8b60c (blog), + this docs commit.

**Hard-won facts (carry forward):**

- **Match model soft-deletes** — clean() must use `withTrashed()` +
  `forceDelete()` or "deleted" seed matches linger and re-adopt relations.
- **DUMP-ORPHAN ID-REUSE trap:** prod-deleted matches above id 22436 left
  orphaned rows keyed to those ids in timelineables / team_match /
  match_caster / match_channel / games / substitutes (3891 / 7942 / 817 /
  667 / 2 / 319 rows). Freshly seeded matches **adopt** them via
  auto-increment id reuse (ghost casters/games/timeline on brand-new
  matches). clean() now purges rows referencing nonexistent matches first.
- Timeline pivot is the **polymorphic `rikki_heroeslounge_timelineables`** —
  there is no `timeline_match` table.
- Calendar (`UpcomingMatches type=all`) lists only `winner_id IS NULL` matches
  with `wbp` in [today, +100d] — `wbp NULL` never shows there.
- Full run takes **~30 min**: the frozen `DivisionTableFix` recomputes
  standings on EVERY match save. Expected; don't kill it.
- RNG is seeded but **wall-clock-coupled**: the played/upcoming split hinges
  on `now()`, so reruns on different days shift which matches are future.
- Frozen `free_win_count` clobber: BYE free wins read **0** in standings (the
  bye pivot row itself survives) — a frozen-plugin artifact, not a seed bug.

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
- Content operation: create/confirm the Indikator.Content category with slug
  `events` in production before cutover so the nav Events target
  `/blog/category/events` resolves with content.
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
  calendar/blog (done) + match-detail (Task 5 built; header/scheduled/tabs render
  live). Still blocked on data even with this dump: upcoming-match rendering,
  match-detail **per-game statistics + pre-game rosters** (gameparticipation
  empty / no-games matches have 0 team_match rows — all fixture-blind, see the
  Task-5 notes + DB-DUMP-IMPORT.md caveats). NOTE the Task-5 dev-DB schema fix
  (gameparticipation columns) must be re-applied after any re-import.
