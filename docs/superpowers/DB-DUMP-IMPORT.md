# Real DB Dump — Import Record & Bridge Notes

_Created 2026-07-08. This documents importing the real production dump
`hl_test_data_dump_05_2024.sql` (May 2024, MySQL 8.0, ~55 MB) into the local
Docker dev environment, and the workarounds needed because the dump is
**partial**. It complements — and where it differs, supersedes — the "Swapping
in the real team dump later" section of `dev/README.md`._

> **TL;DR:** The dump imports and the site runs on real data (`heroeslounge-next`
> theme), but the dump omitted several tables. `team_match` (match participants +
> scores) was reconstructed from `games` (~95% of matches). For full fidelity,
> obtain a **complete `mysqldump` with no table filters** — that removes every
> workaround below.

---

## Outcome

Real data now live in the dev DB and verified rendering on the new theme:

| Page | URL | Status |
|---|---|---|
| Homepage | `/` | ✅ 200 — seasons, standings, matches, blog |
| Season overview | `/eu-season-23` (active season) | ✅ 200 |
| Division page | `/eu-season-23/division-1` | ✅ 200 — standings + rounds |
| Calendar | `/calendar` | ✅ 200 |
| Blog list | `/blog` | ✅ 200 |
| Match detail | `/match/view/:id` | ✅ 200 — **Task 5 built** (header/rosters/scheduled + game tabs); per-game stats fixture-blind (`gameparticipation` empty) |
| Team page | `/team/view/:slug` | 404 — **page not built yet** (Phase 2 Task 7) |

Real-data volume: 68 seasons, 554 divisions, 2,747 teams, 14,012 sloths,
22,345 matches, 49,395 games, 414 blog posts.

Active season in this dump: **`eu-season-23`** ("[EU] Season 23"). Note this is
NOT `season-30` (that was fixtures-only) — update any bookmarked test URLs.

---

## The dump is partial — what was missing

The dump targets DB `hl_main` (MySQL 8.0) and was exported without several
tables. Two classes of gap:

### A. Genuinely-missing plugin tables (had to be recreated)

Created empty from the frozen plugin migrations
(`plugins/rikki/heroeslounge/updates/builder_table_create_*`):

- **`rikki_heroeslounge_team_match`** — the pivot storing *which two teams
  played each match and the series score*. Its absence 500s every page that
  renders a match card. **Reconstructed** (see below).
- `rikki_heroeslounge_gameparticipation` + `_talent` — per-game draft / hero /
  talent picks. **Left empty** (no source to reconstruct from). Affects the
  Task-5 match-detail per-game breakdown. **Schema gap (found Task 5b, 2026-07-10):**
  the recreation ran only `builder_table_create_gameparticipation`, so the table
  was missing every column the later `_update_*` migrations add — `team_id` + the
  9 stat columns. `GameStatistics::onRender()` eager-loads `gameParticipations`
  with a `byTeam` scope (`orderBy('team_id')`) for any game with a `winner_id`, so
  **every match that HAS games 500'd** (`SQLSTATE 42S22 Unknown column 'team_id'
  in 'order clause'`) — in the plugin PHP, before any markup renders (would 500
  the OLD theme identically). Fixed by bringing the table to production schema
  (ALTER in step 5 below); table stays 0-rows so per-game stats remain
  fixture-blind, but the page no longer crashes.
- `rikki_heroeslounge_team_apps` — team join applications. Left empty.

### B. Missing core System tables (had to be recreated)

- `system_event_logs`, `system_request_logs`, `system_sessions` — transient
  log/session tables. Their absence turned every 404 into a 500 (October tries
  to write a request-log row on 404). Recreated via `october:up` after clearing
  their migration records.

### C. Tables the dump correctly omits (do NOT recreate)

`country`, `sloth_archive`, `playoff_team`, `game`, `timeline_relation` — these
were created then dropped/renamed by the plugin's own migration history
(superseded by `team_playoff`, `games`, `timelineables`, etc.). Only referenced
in migration files, never at runtime. Their absence is correct.

---

## `team_match` reconstruction

The `games` table records `team_one_id`, `team_two_id`, `winner_id` per game, so
played-match participants and series scores are recoverable — this is the same
derivation the plugin does (`Game::afterSave` increments the winner's
`team_score`). Rebuilt with:

```sql
INSERT INTO rikki_heroeslounge_team_match (team_id, match_id, team_score, created_at, updated_at)
SELECT p.team_id, p.match_id,
       (SELECT COUNT(*) FROM rikki_heroeslounge_games g
          WHERE g.match_id = p.match_id AND g.winner_id = p.team_id AND g.deleted_at IS NULL),
       NOW(), NOW()
FROM (
    SELECT match_id, team_one_id AS team_id FROM rikki_heroeslounge_games WHERE deleted_at IS NULL AND team_one_id IS NOT NULL
    UNION
    SELECT match_id, team_two_id AS team_id FROM rikki_heroeslounge_games WHERE deleted_at IS NULL AND team_two_id IS NOT NULL
) AS p
GROUP BY p.match_id, p.team_id;
```

Result: **36,678 rows across 18,339 matches** (~95%).

**Not recoverable:** ~1,200 matches whose games carried no team IDs (free
wins / forfeits / walkovers), **including all 624 upcoming matches** (they have
no games at all). Their participants exist only in the real `team_match`.

Because the frozen `Division::getDivisionTableStandings()`
(`plugins/rikki/heroeslounge/models/Division.php:186`) dereferences
`$match->loser->id` without a null guard, **3,203 played matches** that had a
`winner_id` but no reconstructable opponent were nulled out so the standings
code skips them:

```sql
UPDATE rikki_heroeslounge_match SET winner_id = NULL
WHERE winner_id IS NOT NULL AND winner_id > 0
  AND id NOT IN (SELECT match_id FROM rikki_heroeslounge_team_match);
```

Only 60 of those 3,203 are in the active season; the rest are historical.

---

## Indikator blog shim adaptation

The real `indikator_content_blog` schema differs from the dev shim's fixture
schema. Adapted `dev/docker/plugins/indikator/content/models/{Blog,Category}.php`:

- Published flag: shim's boolean `published` → real **`status`** column
  (`1` = published, `2` = draft).
- Categories: shim's `indikator_content_blog_category` pivot →
  real **`indikator_content_blog_relations(blog_id, blog_categories_id)`**;
  category table `indikator_content_categories` →
  **`indikator_content_blog_categories`**.

> ⚠️ **These two files are modified in the working tree (uncommitted).** They now
> match the **real** Indikator schema, which makes them **incompatible with
> `fixtures:seed`** (whose `create_blog_tables.php` still builds the old fixture
> schema). Pick one path — real dump *or* fixtures — don't mix. Revert these two
> files to go back to fixtures.

---

## How to reproduce the full import (complete, from scratch)

From repo root, Docker Desktop running:

```powershell
# 1. Bring up containers
docker compose -f dev/docker-compose.yml up -d

# 2. Recreate the schema empty as utf8mb4
docker compose -f dev/docker-compose.yml exec -T db mysql -uroot -proot -e "DROP DATABASE IF EXISTS heroeslounge; CREATE DATABASE heroeslounge CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; GRANT ALL ON heroeslounge.* TO 'october'@'%';"

# 3. Import, stripping the dump's CREATE DATABASE / USE hl_main header (bash / git-bash)
sed '/^CREATE DATABASE .*`hl_main`/d; /^USE `hl_main`/d' hl_test_data_dump_05_2024.sql \
  | docker compose -f dev/docker-compose.yml exec -T db mysql -uroot -proot heroeslounge
```

Then, **only needed because THIS dump is partial** (a complete dump skips 4–6):

```powershell
# 4. Backfill core migration bookkeeping so october:up doesn't recreate existing tables,
#    but DO let it create the 3 genuinely-missing transient core tables.
#    (See the per-step SQL in git history / this session; net effect: migrations table
#     lists the 41 core migrations as applied EXCEPT event_logs/request_logs which run.)

# 5. Recreate the 4 missing plugin tables (tinker running their migration up()):
#    team_match, gameparticipation, gameparticipation_talent, team_apps
#    ⚠ Running only the builder_table_create_* migration leaves gameparticipation
#    missing the columns the _update_* migrations add — WITHOUT them every
#    with-games match 500s in GameStatistics (byTeam scope orderBy team_id).
#    Bring it to production schema:
#      ALTER TABLE rikki_heroeslounge_gameparticipation
#        ADD COLUMN team_id                 int(10) unsigned DEFAULT NULL,
#        ADD COLUMN draft_order             int(10) unsigned DEFAULT NULL,
#        ADD COLUMN kills                   int(10) unsigned DEFAULT NULL,
#        ADD COLUMN deaths                  int(10) unsigned DEFAULT NULL,
#        ADD COLUMN assists                 int(10) unsigned DEFAULT NULL,
#        ADD COLUMN experience_contribution int(10) unsigned DEFAULT NULL,
#        ADD COLUMN healing                 int(10) unsigned DEFAULT NULL,
#        ADD COLUMN siege_damage            int(10) unsigned DEFAULT NULL,
#        ADD COLUMN hero_damage             int(10) unsigned DEFAULT NULL,
#        ADD COLUMN damage_taken            int(10) unsigned DEFAULT NULL;

# 6. Reconstruct team_match from games (SQL above), then null unrecoverable winners (SQL above)

# 7. Apply remaining migrations, activate theme, clear cache
docker compose -f dev/docker-compose.yml exec -T web php artisan october:up
docker compose -f dev/docker-compose.yml exec -T web php artisan theme:use heroeslounge-next
docker compose -f dev/docker-compose.yml exec -T web php artisan cache:clear
```

> With a **complete** dump, steps 4–6 vanish: import (steps 1–3), then
> `october:up` + `theme:use` + `cache:clear`. That is the target state.

---

## Caveats carried forward

- **Not verifiable against real data on this dump:** upcoming-match rendering
  (homepage "next match", calendar upcoming, division upcoming — no participants
  for the 624 upcoming matches), match-detail per-game statistics (the Task-5
  page IS built, but `gameparticipation*` is empty → every game renders the empty
  branch; hero picks / bans / stats table / talents / replay download are all
  fixture-blind), match-detail pre-game rosters (no-games matches have 0
  `team_match` rows → the bye-guard always fires), team-application flows.
- **Standings accuracy:** win counts come from the `team_division` pivot (intact,
  real), so standings are correct; only head-to-head *tiebreaks* on the 60
  nulled active-season matches are slightly affected.
- **All reconstruction lives in the Docker volume, not git.** `down -v` or
  `fixtures:seed` wipes it — re-import to restore.
- **Persisted logins:** the anonymised dump convention sets fixture passwords
  (repo README notes `1234`); real accounts are anonymised per
  `anonymize_backup.sql`.
- **The dump file** (`hl_test_data_dump_05_2024.sql`) is git-ignored (root `/*`
  rule) — it will not be committed.

## Recommended next step for data fidelity

Ask whoever produced the dump to re-run a **complete** export (no `--tables` /
`--ignore-table` filters), including at minimum `rikki_heroeslounge_team_match`,
`rikki_heroeslounge_gameparticipation`, `rikki_heroeslounge_gameparticipation_talent`,
and `rikki_heroeslounge_team_apps`. Re-import to drop every workaround here.
