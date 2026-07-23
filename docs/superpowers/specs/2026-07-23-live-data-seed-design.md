# Live-data seed for the active 2026 seasons — design

**Date:** 2026-07-23 · **Status:** approved by user (design conversation), pending spec review
**Branch:** `ui-rework-phase-2`

## Problem

The fresh `hl_test_data_dump_07_2026.sql` is loaded and clean, but historically
frozen: the newest match row is 2024-04-28, the two active 2026 seasons
(`eu-season-30` "[EU] Season 30", `NMMR3` "Nexus MM Rumble 3") have divisions and
teams but **zero generated match fixtures**, and `gameparticipation` is empty.
Consequently the calendar, division rounds/standings sidebars, and upcoming-match
widgets all render their (correct) empty states. The blog has no `events`
category (the nav's `/blog/category/events` link 404s) and no recent content —
plan-review correction: the KNOWN-ISSUES audit's "1 post / only Uncategorized"
claim is WRONG; the dump actually has 439 posts (425 published, newest
2026-05-10) and 27 categories (incl. singular `event`, but not `events`).

Goal: make the dev site *look live* — populated division rounds, standings,
calendar, timeline, team match history, homepage widgets, blog/events — **without
touching frozen code or destroying the real dump data**.

## Constraints (binding)

- `plugins/rikki/*`, `rainlab`, the `indikator` shim, and `themes/HeroesLounge-Theme/`
  are FROZEN. `themes/heroeslounge-next` needs no changes for this task.
  `plugins/dev/fixtures` is **dev-only and editable** — this is where all new code goes.
- The imported dump is precious: no truncation of shared tables, no mutation of
  rows the seeder did not create. The legacy `fixtures:seed` (wipe-and-reseed)
  stays untouched and is NOT run.
- Season rows are **never mutated** (`current_round`, `mm_active`, `reg_open` stay
  as imported). The seed targets the state the season rows already claim:
  Season 30 `current_round=3` of 10, NMMR3 `current_round=1`.
- Per-game statistics stay blind: `gameparticipation` remains 0 rows (dump
  limitation, explicitly out of scope).

## Established facts (verified in this repo/DB, 2026-07-23)

- **Real generation path:** `Rikki\Heroeslounge\classes\Matchmaking\Swiss` —
  `doMM($season)` runs weekly per region via `Plugin::registerSchedule`, creates
  ONE round per run. Its pairing step (`findMatching`) shells out to
  **python3** (`matching.py` → `mwmatching.py`); python3 is NOT installed in the
  dev web container (`python3: not found`). Hence: PHP pairing, frozen models.
- **Bookkeeping is automatic through Eloquent:** `Match::afterSave` increments
  `team_division.win_count`, creates the `Match.Played` timeline entry +
  `match_count` increments, defaults `wbp` to now-if-null, and finally runs
  `hotfixes\DivisionTableFix::fixTables($season)` which **recomputes**
  `win_count`/`match_count`/`free_win_count` for every team of every division of
  the season from the match table. `Match::determineWinnerAndSave()` derives the
  match winner from its `Game` rows (equal game wins → `winner_id NULL`, a draw,
  `is_played=1`).
- **Calendar window:** `UpcomingMatches` `type=all` lists matches with
  `winner_id IS NULL AND wbp BETWEEN today AND today+daysInFuture` (theme page
  passes `daysInFuture=100`). Matches with `wbp NULL` never appear anywhere in
  UpcomingMatches (SQL excludes). So the calendar populates **only** if some
  unplayed matches get a future `wbp`.
- **DB state:** Season 30 = season id 92, divisions 757–761 with 12/16/19/12/15
  teams, all pivot-`active`. NMMR3 = season id 93, division 762 `3NMMRO` (6 teams)
  + division 763 `3000nmmr3` (**0 teams** — skip). `BYE!` team exists, id 204
  (matches the Swiss default). 19 rows in `rikki_heroeslounge_maps`.
  **0 matches** exist under divisions of either season.
- **Creation is Eloquent, not the frozen raw inserts.** In THIS dump
  `rikki_heroeslounge_match.is_played` has `DEFAULT '0'` (spec-review verified —
  the legacy seeder's strict-`sql_mode` hazard note is stale for this schema),
  so raw inserts would not error; Eloquent creation with explicit `is_played=0`
  is chosen anyway because saving through the model is what triggers the frozen
  `afterSave` bookkeeping (proven pattern from the legacy seeder's `createMatch`).
- Matches table/columns: `rikki_heroeslounge_match`, FK **`div_id`** (not
  division_id); team↔match pivot `rikki_heroeslounge_team_match`; timeline table
  singular `rikki_heroeslounge_timeline`, linked to matches via the
  **polymorphic** pivot `rikki_heroeslounge_timelineables` (`morphToMany`
  `timelineable` — there is no `timeline_match` join table).

## Design

### Component: `fixtures:live-data` console command

New `plugins/dev/fixtures/console/SeedLiveData.php`, registered in
`Dev\Fixtures\Plugin` next to `fixtures:seed`. Options: `--force` (skip
confirmation), `--skip-blog`. **Additive + scoped**, the opposite philosophy of
`fixtures:seed`.

Run shape:

1. **Guard.** Resolve seasons by slug (`eu-season-30`, `NMMR3`) requiring
   `is_active=1`; resolve their divisions and team rosters; abort with a clear
   error if anything is missing (wrong-DB protection). Fixed RNG seed
   (`mt_srand`) → deterministic output.
2. **Clean (idempotency).** Collect the `Match.Played` timeline ids via
   `$match->timeline()` **before** deleting (the polymorphic pivot has no
   simple join to walk afterwards; `afterDelete` does NOT delete timeline
   entries), then delete all matches whose `div_id` belongs to those divisions
   (Eloquent `delete()` → `afterDelete` detaches teams/casters and deletes
   games), delete the collected timeline entries, and reset those divisions'
   `team_division` pivot counters (`win_count`, `match_count`,
   `free_win_count`, `bye`) to 0. **All matches under the target divisions are
   seeder-owned by definition** (0 exist in the pristine dump) — re-runs must
   NOT abort on finding them.
3. **Generate rounds** (per division; algorithm below).
4. **Timeline backdate polish.** Set each generated `Match.Played` timeline
   entry's `created_at` to its match's `wbp` so the division/team sidebars read
   chronologically instead of "everything happened today".
5. **Blog phase** (unless `--skip-blog`; see below).
6. **Summary output** (counts per division/round, played vs upcoming vs
   unscheduled, blog posts created).

### Generation algorithm

Target snapshot — **"round N in progress"**, consistent with each season row:

- Season 30 (divisions 757–761): rounds 1–2 **fully played**; round 3 current:
  ~40% played over the last few days, the rest **scheduled with future `wbp`**
  spread across the next 5–7 evenings, minus 1–2 matches per division left
  `wbp NULL` (exercises "not scheduled yet" on the match page).
- NMMR3 (division 762 only): round 1 in progress with the same mix (6 teams →
  3 matches: 1 played, 1 future-scheduled, 1 unscheduled).

Per round, per division:

- **Pairing.** Round 1: shuffled random pairing. Later rounds: order teams by
  cumulative seeded wins (then map score) and pair adjacent, greedily skipping
  pairs that already met in this division (mirrors `Swiss::sortedTeams` +
  `isValidPairing` semantics; a simple greedy is acceptable — demo fidelity, not
  tournament correctness).
- **BYE.** Odd roster (divisions 759: 19 teams, 761: 15 teams) → the
  lowest-standing team without a previous BYE pairs against team 204, mirroring
  the frozen BYE branch: winner = real team, `is_played=1`, backdated `wbp`,
  2 `Game` rows, `team_division.free_win_count` incremented, pivot `bye` marked
  for the receiving team. (The `DivisionTableFix` recompute may subsequently
  clobber `free_win_count` — that is frozen-prod behavior; accept whatever state
  the frozen recompute produces.)
- **Match creation (Eloquent, frozen models).** `new Match` with `div_id`,
  `round`, explicit `is_played=0`, backdated `created_at`/`updated_at`, and the
  Swiss date convention: `schedule_date` = that round's Sunday 23:55
  Europe/Amsterdam, `tbp` = one week later. Attach both teams via
  `$match->teams()->save(...)`.
- **Played matches.** Bo2: two `Game` rows (distinct random real maps, per-game
  `winner_id`), outcome weighted 2-0 / 0-2 / 1-1 ≈ 40/40/20. Set `wbp` to a
  realistic evening (19:00–22:00 CET) within the round's week **before** calling
  the frozen `determineWinnerAndSave()` (afterSave only defaults `wbp` when
  null). 1-1 → `winner_id NULL`, `is_played=1`: a draw — legitimate, appears in
  rounds/standings but never in the calendar (wbp is past).
- **Upcoming matches.** No games, no winner; future `wbp` (calendar) or `wbp
  NULL` (unscheduled).

Season rows, frozen plugins, other seasons' data: untouched.

### Blog phase

Via the Indikator shim models (`Indikator\Content\Models\Blog`, `Category` —
reference: legacy `seedBlog()`):

- Ensure category **`events`** exists (fixes the Events nav → 404, item §3.2 of
  KNOWN-ISSUES: nav points at `/blog/category/events`; the dump has only the
  singular `event`).
- Upsert ~6–8 published posts **by seeder-owned slug** (delete+recreate only
  slugs the seeder owns; the dump's ~439 real posts are untouched): league-news
  posts + 2–3 events posts, `published_at` staggered over recent weeks (fresher
  than the dump's newest 2026-05-10 post), clearly dev-flavored content. NOTE
  the real Indikator schema: `status` varchar '1'=published (no `published`
  column), jsonable `images`/`files` + `related_*` text columns are NOT NULL
  with no default and must be set explicitly.

### Out of scope

- Per-game statistics (`gameparticipation` = 0 rows — dump limitation).
- Season 30 playoffs (round 3 of 10; none would exist yet in reality).
- NMMR3 division 763 (0 teams).
- Any frozen-plugin/theme change; any prod-bound code.

## Error handling

- Wrong-DB guard: abort before any write if the seasons (by slug, `is_active=1`),
  their divisions, or the BYE team are missing. That is the WHOLE guard — there
  is no marker distinguishing seeder-created matches, so matches found under the
  target divisions are treated as seeder-owned and cleaned; do NOT implement an
  abort-if-matches-exist check (it would break idempotent re-runs).
- Confirmation prompt unless `--force` (consistent with `fixtures:seed`).
- Deterministic RNG so re-runs give identical data (bar `created_at` "now"
  moments that are backdated anyway).

## Verification plan (live site, after one run)

1. `/eu-season-30/division-1`: standings with W/L + signed map±; round tabs 1–3
   populated; sidebar recent results, upcoming (future wbp), timeline.
2. `/eu-season-30/division-3` (BYE division): BYE handled, standings sane.
3. `/calendar`: multiple future date groups within the next week.
4. `/NMMR3` + `/NMMR3/3NMMRO`: round 1 renders with matches.
5. Match detail: one played (winner, empty per-game stats — expected), one
   future-scheduled (viewer-TZ time), one unscheduled ("not scheduled yet").
6. `/team/view/:slug` for a seeded Season-30 team: match history groups.
7. Homepage: upcoming-matches + recent-results widgets populated; blog strip.
8. `/blog` and `/blog/category/events`: HTTP 200, posts render.
9. `storage/logs/system.log`: 0 ERROR/exception lines after the sweep.
10. Idempotency: run the command a second time → identical row counts, no
    duplicates, site unchanged.

## Deliverables

- `plugins/dev/fixtures/console/SeedLiveData.php` + registration in
  `plugins/dev/fixtures/Plugin.php`.
- `dev/README.md`: short section for the new command (what it does, that it is
  additive/scoped and re-runnable, `--force`/`--skip-blog`).
- Doc updates: PROGRESS.md (task entry + notes), KNOWN-ISSUES.md (items 2, 4, 5,
  7 resolved-by-data; stats item 6 stays), NEXT-SESSION.md launch pad.
