# Live-Data Seed (`fixtures:live-data`) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** A dev-only, additive, re-runnable `php artisan fixtures:live-data` command that seeds Swiss-style match rounds for the two active 2026 seasons (`eu-season-30`, `NMMR3`) plus an `events` blog category and posts, so the dev site demos live data (division rounds, standings, calendar, timeline, team history, homepage widgets, blog).

**Architecture:** One new console command in the editable `plugins/dev/fixtures` plugin (frozen plugins untouched). Pairing is computed in PHP (Swiss approximation); all writes go through the frozen Eloquent models so `Match::afterSave` / `Game::afterSave` / `DivisionTableFix` do the standings, team_score, and timeline bookkeeping. Blog content is upserted by seeder-owned slugs via the Indikator shim models.

**Tech Stack:** October CMS v1 (Laravel 6, PHP 7.x) inside `dev/docker-compose.yml` (`heroeslounge-dev-web-1` + MySQL 5.7 `heroeslounge-dev-db-1`). No tests exist for dev plugins — verification is run-the-command + SQL/HTTP assertions (repo convention for all prior tasks).

**Spec:** `docs/superpowers/specs/2026-07-23-live-data-seed-design.md` — read it first; its "Established facts" section is pre-verified against the code and live DB.

---

## Context crash-course (read before Task 1)

- **Environment.** Containers must be up: `docker compose -f dev/docker-compose.yml up -d` from repo root. Run artisan via `docker exec heroeslounge-dev-web-1 php artisan ...`, SQL via `docker exec heroeslounge-dev-db-1 mysql -uroot -proot heroeslounge -e "..."`. Site: http://localhost:8090.
- **DB facts (verified 2026-07-23):** Season 30 = seasons id **92**, slug `eu-season-30`, `current_round=3`; NMMR3 = id **93**, slug `NMMR3`, `current_round=1`. Divisions: **757**(12 teams)/**758**(16)/**759**(19)/**760**(12)/**761**(15) for S30; **762** `3NMMRO` (6) + **763** (0 teams → must be skipped) for NMMR3. All rosters pivot-`active`. `BYE!` team id **204**. 19 rows in `rikki_heroeslounge_maps`. **0 matches** exist under any of these divisions.
- **Frozen model behavior you rely on (do NOT re-implement):**
  - `Match::afterSave` (Match.php:301): when `winner_id` becomes dirty → increments `team_division.win_count`, creates a `Match.Played` `Timeline` entry + increments `match_count` for both teams, sets `is_played=1`, defaults `wbp` to now **only if null**, advances playoffs (n/a here); then **always** runs `DivisionTableFix::fixTables($season)` which recomputes `win_count`/`match_count`/`free_win_count` for the whole season from the match table.
  - `Match::determineWinnerAndSave()` (Match.php:387): derives winner from the match's `Game` rows; equal games → `winner_id NULL`, `is_played=1` (a draw — gets NO timeline entry; that is frozen behavior, keep it).
  - `Game::afterSave` increments `team_match.team_score` for the game winner; `Game::afterDelete` decrements. `Match::afterDelete` detaches teams/casters and deletes games — it does NOT touch timeline entries.
  - **`Match` uses SoftDelete** (Match.php:26). The clean step must use `withTrashed()` + `forceDelete()` or soft-deleted rows accumulate across re-runs. `Timeline` and `Game` are plain models (hard delete).
  - `Match::afterUpdate` only fires a Discord webhook when `wbp` *changes* AND casters are attached — our matches never attach casters, so no external calls. (Legacy seeder's `disableExternalModelEvents()` flushes **Sloth** listeners only; we never save Sloths, so we don't need it.)
- **Timeline pivot is polymorphic:** `rikki_heroeslounge_timelineables` via `$match->timeline()` (morphToMany, Match.php:67-71). Collect + detach **before** deleting the match.
- **Calendar query** (`UpcomingMatches` type=all): `winner_id IS NULL AND wbp BETWEEN today AND today+100 days`. `wbp NULL` never shows. So upcoming matches need a future `wbp`; 1–2 per division stay `wbp NULL` deliberately (renders "not scheduled yet" on match pages).
- **Swiss conventions mirrored** (Swiss.php): `schedule_date` = the round-week's Sunday 23:55 Europe/Amsterdam; `tbp` = one week later; MM runs Mondays 01:00 (so `created_at` = round-week Monday ~01:05); BYE receiver = lowest-standing team without a previous BYE; BYE match = instant win for the real team with 2 games + `free_win_count`/`bye` pivot bookkeeping.
- **Legacy reference:** `plugins/dev/fixtures/console/SeedFixtures.php` — `createMatch()` (line ~470) is the proven Eloquent creation pattern; `seedBlog()` (line ~684) the blog pattern. Do NOT run `fixtures:seed` — it truncates the real dump.
- **Git gotcha:** commit with `git -c core.fsmonitor=false commit ...`; if `index.lock` exists and no git op is running, delete it and retry. Commit trailer: `Co-Authored-By: Claude Fable 5 <noreply@anthropic.com>`.
- **Expected runtime:** EVERY match save (including bare unplayed `createMatchRow` saves — the recompute is the unconditional tail of `Match::afterSave`) triggers the frozen full-season `DivisionTableFix` recompute → the full seed takes a few minutes. That is accepted (frozen behavior, dev-only, one-shot).

---

### Task 1: Command skeleton — registration, guard, clean step

**Files:**
- Create: `plugins/dev/fixtures/console/SeedLiveData.php`
- Modify: `plugins/dev/fixtures/Plugin.php` (one line in `register()`)

- [ ] **Step 1: Create the command file** with the full class below (generation/blog methods land in Tasks 2–3; the stubs keep it runnable):

```php
<?php namespace Dev\Fixtures\Console;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Input\InputOption;

use Rikki\Heroeslounge\Models\Season;
use Rikki\Heroeslounge\Models\Team;
use Rikki\Heroeslounge\Models\Match as HlMatch;
use Rikki\Heroeslounge\Models\Game;
use Rikki\Heroeslounge\Models\Map;
use Indikator\Content\Models\Blog;
use Indikator\Content\Models\Category;

/**
 * DEV-ONLY: additive live-data seed for the ACTIVE seasons of the imported
 * real dump. The opposite philosophy of fixtures:seed (which truncates
 * everything and must NOT be run against the dump):
 *
 *  - Only creates/deletes matches under the divisions of the two active
 *    seasons (eu-season-30, NMMR3). Those have 0 matches in the pristine
 *    dump, so everything under them is seeder-owned by definition.
 *  - Never mutates season rows (current_round/mm_active stay as imported).
 *  - Re-runnable: each run cleans its own previous output first.
 *  - Blog phase upserts posts by seeder-owned slugs (prefix "dev-") and
 *    creates the "events" category if missing; the dump's real post is
 *    untouched.
 *
 * All match/game writes go through the frozen Eloquent models so the
 * production bookkeeping (Match::afterSave -> win_count/match_count pivots,
 * Match.Played timeline entries, DivisionTableFix recompute; Game events ->
 * team_match.team_score) runs exactly as on prod.
 */
class SeedLiveData extends Command
{
    protected $name = 'fixtures:live-data';

    protected $description = 'DEV ONLY - additive, re-runnable live-data seed for the active seasons (+ blog/events).';

    const RNG_SEED = 30;
    const AMS_TZ = 'Europe/Amsterdam';
    const SEASON_SLUGS = ['eu-season-30', 'NMMR3'];
    const BLOG_SLUG_PREFIX = 'dev-';

    /** @var Team */
    protected $byeTeam;

    /** @var array plain array of Map models */
    protected $maps = [];

    /** @var string */
    protected $appTz;

    // In-memory per-division state driving Swiss-ish pairing.
    protected $wins = [];      // [divId][teamId] => int
    protected $mapScore = [];  // [divId][teamId] => int
    protected $playedPairs = []; // [divId]["minId-maxId"] => true
    protected $byesGiven = [];   // [divId][teamId] => true

    // Stats for the summary.
    protected $counts = ['cleaned' => 0, 'created' => 0, 'played' => 0, 'draws' => 0, 'byes' => 0, 'upcoming' => 0, 'unscheduled' => 0];

    public function handle()
    {
        if (!$this->option('force')) {
            $ok = $this->confirm(
                'This deletes ALL matches under the active seasons (eu-season-30, NMMR3) - '
                . 'seeder-owned by definition - and regenerates them, plus dev blog posts. Continue?'
            );
            if (!$ok) {
                $this->output->writeln('<comment>Aborted - nothing was changed. Use --force to skip this prompt.</comment>');
                return 1;
            }
        }

        mt_srand(self::RNG_SEED);
        $this->appTz = config('app.timezone');

        $seasons = $this->resolveTargets();
        if ($seasons === null) {
            return 1; // guard already printed the reason
        }

        $this->clean($seasons);

        foreach ($seasons as $season) {
            $this->generateSeason($season);
        }

        if (!$this->option('skip-blog')) {
            $this->seedBlog();
        }

        $this->output->writeln('');
        $this->output->writeln(sprintf(
            '<info>Done. cleaned=%d created=%d (played=%d incl. draws=%d, byes=%d; upcoming=%d, unscheduled=%d)</info>',
            $this->counts['cleaned'], $this->counts['created'], $this->counts['played'],
            $this->counts['draws'], $this->counts['byes'], $this->counts['upcoming'], $this->counts['unscheduled']
        ));
        return 0;
    }

    protected function getOptions()
    {
        return [
            ['force', 'f', InputOption::VALUE_NONE, 'Skip the confirmation prompt (for scripted use).'],
            ['skip-blog', null, InputOption::VALUE_NONE, 'Seed match data only; leave the blog untouched.'],
        ];
    }

    /**
     * Wrong-DB guard: the target seasons (by slug, active), at least one
     * populated division, the BYE team and some maps must exist. This is the
     * WHOLE guard - matches found under the target divisions are seeder-owned
     * by definition (0 exist in the pristine dump), so re-runs never abort.
     *
     * @return array|null array of Season models, or null to abort
     */
    protected function resolveTargets()
    {
        $seasons = [];
        foreach (self::SEASON_SLUGS as $slug) {
            $season = Season::where('slug', $slug)->where('is_active', 1)->first();
            if (!$season) {
                $this->output->writeln("<error>Active season '$slug' not found - is the real 07/2026 dump imported? Aborting.</error>");
                return null;
            }
            $seasons[] = $season;
        }

        $this->byeTeam = Team::where('title', 'BYE!')->first();
        if (!$this->byeTeam) {
            $this->output->writeln("<error>BYE! team not found - wrong database? Aborting.</error>");
            return null;
        }

        $this->maps = Map::all()->values()->all();
        if (count($this->maps) < 2) {
            $this->output->writeln('<error>Fewer than 2 maps in rikki_heroeslounge_maps - wrong database? Aborting.</error>');
            return null;
        }

        foreach ($seasons as $season) {
            $divs = $season->divisions()->get();
            $this->output->writeln(sprintf(
                '  target: %s (id %d, current_round %d) - %d division(s)',
                $season->slug, $season->id, $season->current_round, $divs->count()
            ));
        }
        return $seasons;
    }

    /**
     * Idempotency: force-delete every match under the target divisions
     * (Match soft-deletes -> withTrashed + forceDelete, or rows accumulate),
     * with their Match.Played timeline entries (collected BEFORE the delete -
     * the polymorphic pivot cannot be walked afterwards and afterDelete does
     * not clean it), then zero the standings pivots.
     */
    protected function clean(array $seasons)
    {
        $divIds = [];
        foreach ($seasons as $season) {
            foreach ($season->divisions()->get() as $div) {
                $divIds[] = $div->id;
            }
        }

        $matches = HlMatch::withTrashed()->whereIn('div_id', $divIds)->get();
        foreach ($matches as $match) {
            $timelines = $match->timeline()->get();
            $match->timeline()->detach();
            foreach ($timelines as $timeline) {
                $timeline->delete();
            }
            $match->forceDelete(); // afterDelete detaches teams/casters + deletes games
            $this->counts['cleaned']++;
        }

        DB::table('rikki_heroeslounge_team_division')
            ->whereIn('div_id', $divIds)
            ->update(['win_count' => 0, 'match_count' => 0, 'free_win_count' => 0, 'bye' => 0]);

        $this->output->writeln('  cleaned: ' . $this->counts['cleaned'] . ' seeder-owned match(es)');
    }

    protected function generateSeason($season)
    {
        // Task 2
    }

    protected function seedBlog()
    {
        // Task 3
    }
}
```

- [ ] **Step 2: Register the command** in `plugins/dev/fixtures/Plugin.php` — inside `register()`, directly under the existing `registerConsoleCommand` line, add:

```php
        $this->registerConsoleCommand('fixtures.livedata', 'Dev\Fixtures\Console\SeedLiveData');
```

- [ ] **Step 3: Run the skeleton — guard + clean on the pristine DB**

Run: `docker exec heroeslounge-dev-web-1 php artisan fixtures:live-data --force`
Expected output (order may vary slightly):
```
  target: eu-season-30 (id 92, current_round 3) - 5 division(s)
  target: NMMR3 (id 93, current_round 1) - 2 division(s)
  cleaned: 0 seeder-owned match(es)
Done. cleaned=0 created=0 (played=0 incl. draws=0, byes=0; upcoming=0, unscheduled=0)
```

- [ ] **Step 4: Verify no match data was touched**

Run: `docker exec heroeslounge-dev-db-1 mysql -uroot -proot heroeslounge -e "SELECT COUNT(*) AS all_matches FROM rikki_heroeslounge_match; SELECT COUNT(*) AS s30_matches FROM rikki_heroeslounge_match WHERE div_id IN (757,758,759,760,761,762,763);"`
Expected: `all_matches` unchanged from before the run (baseline it first), `s30_matches` = 0.
**Known + sanctioned:** the pristine dump's `team_division` counters for these divisions are non-zero (stale prod values despite 0 matches — e.g. div 757 wins=12/mc=24); `clean()` zeroes them even on this skeleton run. That is spec-intended (the frozen `fixTables` would overwrite them on the first played save anyway) — do not treat it as damage.

- [ ] **Step 5: Commit**

```bash
git -c core.fsmonitor=false add plugins/dev/fixtures/console/SeedLiveData.php plugins/dev/fixtures/Plugin.php
git -c core.fsmonitor=false commit -m "feat(dev-fixtures): fixtures:live-data skeleton - guard + scoped clean"
```
(with the Co-Authored-By trailer)

---

### Task 2: Round generation (pairing, BYE, played simulation, scheduling)

**Files:**
- Modify: `plugins/dev/fixtures/console/SeedLiveData.php` (replace the `generateSeason` stub; add the methods below)

- [ ] **Step 1: Implement generation.** Replace the `generateSeason` stub with:

```php
    protected function generateSeason($season)
    {
        $currentRound = max(1, (int) $season->current_round);

        foreach ($season->divisions()->get() as $div) {
            // Mirror Swiss::sortedTeams roster filters.
            $teams = $div->teams()
                ->where('active', 1)
                ->where('rikki_heroeslounge_teams.disbanded', 0)
                ->whereNull('rikki_heroeslounge_teams.deleted_at')
                ->get()->values();

            if ($teams->count() < 2) {
                $this->output->writeln("  - {$div->slug}: <comment>skipped (fewer than 2 active teams)</comment>");
                continue;
            }

            $this->wins[$div->id] = [];
            $this->mapScore[$div->id] = [];
            $this->playedPairs[$div->id] = [];
            $this->byesGiven[$div->id] = [];
            foreach ($teams as $team) {
                $this->wins[$div->id][$team->id] = 0;
                $this->mapScore[$div->id][$team->id] = 0;
            }

            for ($round = 1; $round <= $currentRound; $round++) {
                $this->generateRound($div, $teams, $round, $currentRound);
            }
            $this->output->writeln("  - {$div->slug}: rounds 1-{$currentRound} generated ({$teams->count()} teams)");
        }
    }

    /**
     * One round for one division. Rounds < currentRound are fully played
     * (backdated into their week); the current round is "in progress": each
     * match gets a random evening slot in THIS week - past slots are played,
     * future slots are upcoming (that is what feeds the calendar), and the
     * last 1-2 upcoming matches lose their wbp (unscheduled state).
     */
    protected function generateRound($div, $teams, $round, $currentRound)
    {
        $amsNow = Carbon::now(self::AMS_TZ);
        $monday = $amsNow->copy()->startOfWeek()->subWeeks($currentRound - $round); // Mon 00:00 Ams of the round's week
        $createdAt = $monday->copy()->addMinutes(65);                    // scheduler runs Mondays 01:00
        $scheduleDate = $monday->copy()->addDays(6)->setTime(23, 55);    // "next sunday 23:55" from Monday
        $tbp = $scheduleDate->copy()->addWeek();
        $isCurrent = ($round === $currentRound);

        $pool = $teams->all(); // plain array of Team models

        // BYE first (odd roster): lowest standing without a previous BYE.
        if (count($pool) % 2 === 1) {
            $byeReceiver = $this->pickByeReceiver($div, $pool, $round);
            $pool = array_values(array_filter($pool, function ($t) use ($byeReceiver) {
                return $t->id !== $byeReceiver->id;
            }));
            $this->createByeMatch($div, $round, $byeReceiver, $monday, $createdAt, $scheduleDate, $tbp);
        }

        $pairings = ($round === 1)
            ? $this->pairRandom($pool)
            : $this->pairByStandings($div, $pool);

        // Current round: random evening slots across the whole week decide
        // played vs upcoming; then null out the last 1-2 upcoming wbps.
        $upcomingIdx = [];
        foreach ($pairings as $i => $pair) {
            $slot = $monday->copy()->addDays(mt_rand(1, 6))->setTime(mt_rand(19, 21), mt_rand(0, 1) * 30);
            if (!$isCurrent || $slot->lt($amsNow)) {
                $this->createPlayedMatch($div, $round, $pair[0], $pair[1], $slot, $createdAt, $scheduleDate, $tbp);
            } else {
                $match = $this->createMatchRow($div, $round, $pair[0], $pair[1], $slot, $createdAt, $scheduleDate, $tbp);
                $upcomingIdx[] = $match->id;
                $this->counts['upcoming']++;
            }
        }

        $nullCount = count($upcomingIdx) >= 3 ? 2 : (count($upcomingIdx) >= 1 ? 1 : 0);
        if ($nullCount > 0) {
            $nullIds = array_slice($upcomingIdx, -$nullCount);
            DB::table('rikki_heroeslounge_match')->whereIn('id', $nullIds)->update(['wbp' => null]);
            $this->counts['upcoming'] -= $nullCount;
            $this->counts['unscheduled'] += $nullCount;
        }
    }

    protected function pairRandom(array $pool)
    {
        shuffle($pool); // PHP >= 7.1: MT-based, deterministic under mt_srand
        $pairings = [];
        for ($i = 0; $i + 1 < count($pool); $i += 2) {
            $pairings[] = [$pool[$i], $pool[$i + 1]];
        }
        return $pairings;
    }

    /**
     * Swiss approximation: order by (wins desc, map score desc, id asc), then
     * greedily pair neighbours avoiding rematches; if a team has already met
     * every remaining candidate, allow the rematch (demo fidelity is enough).
     */
    protected function pairByStandings($div, array $pool)
    {
        usort($pool, function ($a, $b) use ($div) {
            $wa = $this->wins[$div->id][$a->id]; $wb = $this->wins[$div->id][$b->id];
            if ($wa !== $wb) return $wb - $wa;
            $ma = $this->mapScore[$div->id][$a->id]; $mb = $this->mapScore[$div->id][$b->id];
            if ($ma !== $mb) return $mb - $ma;
            return $a->id - $b->id;
        });

        $pairings = [];
        while (count($pool) >= 2) {
            $a = array_shift($pool);
            $pickIdx = 0;
            foreach ($pool as $i => $b) {
                if (!isset($this->playedPairs[$div->id][$this->pairKey($a, $b)])) {
                    $pickIdx = $i;
                    break;
                }
            }
            $b = $pool[$pickIdx];
            array_splice($pool, $pickIdx, 1);
            $pairings[] = [$a, $b];
        }
        return $pairings;
    }

    protected function pickByeReceiver($div, array $pool, $round)
    {
        // Lowest standing first = reverse of pairByStandings order.
        $ordered = $pool;
        usort($ordered, function ($a, $b) use ($div) {
            $wa = $this->wins[$div->id][$a->id]; $wb = $this->wins[$div->id][$b->id];
            if ($wa !== $wb) return $wa - $wb;
            $ma = $this->mapScore[$div->id][$a->id]; $mb = $this->mapScore[$div->id][$b->id];
            if ($ma !== $mb) return $ma - $mb;
            return $a->id - $b->id;
        });
        foreach ($ordered as $team) {
            if (!isset($this->byesGiven[$div->id][$team->id])) {
                return $team;
            }
        }
        return $ordered[0];
    }

    protected function pairKey($a, $b)
    {
        return min($a->id, $b->id) . '-' . max($a->id, $b->id);
    }

    /**
     * Bare unplayed match row via the frozen model (Eloquent so the explicit
     * timestamps are honoured; explicit is_played=0 like the legacy seeder).
     */
    protected function createMatchRow($div, $round, $teamA, $teamB, $wbp, $createdAt, $scheduleDate, $tbp)
    {
        $match = new HlMatch();
        $match->div_id = $div->id;
        $match->round = $round;
        $match->is_played = 0;
        $match->wbp = $wbp ? $this->toApp($wbp) : null;
        $match->schedule_date = $this->toApp($scheduleDate);
        $match->tbp = $this->toApp($tbp);
        $match->created_at = $this->toApp($createdAt);
        $match->updated_at = $this->toApp($createdAt);
        $match->save();

        $match->teams()->attach($teamA->id, ['team_score' => 0]);
        $match->teams()->attach($teamB->id, ['team_score' => 0]);

        $this->playedPairs[$div->id][$this->pairKey($teamA, $teamB)] = true;
        $this->counts['created']++;
        return $match;
    }

    /**
     * Bo2 with outcome weighted 2-0 / 0-2 / 1-1 = 40/40/20, on two distinct
     * random real maps. determineWinnerAndSave + the frozen model events do
     * ALL standings/timeline bookkeeping. The Match.Played timeline entry is
     * then backdated to the match wbp so sidebars read chronologically.
     */
    protected function createPlayedMatch($div, $round, $teamA, $teamB, $wbp, $createdAt, $scheduleDate, $tbp)
    {
        $match = $this->createMatchRow($div, $round, $teamA, $teamB, $wbp, $createdAt, $scheduleDate, $tbp);

        $roll = mt_rand(1, 10);
        if ($roll <= 4) {
            $winners = [$teamA, $teamA];
        } elseif ($roll <= 8) {
            $winners = [$teamB, $teamB];
        } else {
            $winners = [$teamA, $teamB]; // 1-1 draw
            $this->counts['draws']++;
        }

        list($mapOne, $mapTwo) = $this->twoDistinctMaps();
        foreach ([[$mapOne, $winners[0]], [$mapTwo, $winners[1]]] as $spec) {
            $game = new Game();
            $game->match_id = $match->id;
            $game->map_id = $spec[0]->id;
            $game->team_one_id = $teamA->id;
            $game->team_two_id = $teamB->id;
            $game->winner_id = $spec[1]->id;
            $game->save();
        }

        $match = HlMatch::with('teams', 'games')->find($match->id);
        $match->determineWinnerAndSave();
        $this->backdatePlayedTimeline($match);

        // In-memory standings for later pairing.
        foreach ($winners as $w) {
            $this->mapScore[$div->id][$w->id]++;
            $loser = ($w->id === $teamA->id) ? $teamB : $teamA;
            $this->mapScore[$div->id][$loser->id]--;
        }
        if ($winners[0]->id === $winners[1]->id) {
            $this->wins[$div->id][$winners[0]->id]++;
        }
        $this->counts['played']++;
        return $match;
    }

    /**
     * BYE: instant free win for the receiver (mirrors the frozen Swiss BYE
     * branch: played Monday noon, 2 games, free_win_count + bye pivots).
     */
    protected function createByeMatch($div, $round, $receiver, $monday, $createdAt, $scheduleDate, $tbp)
    {
        $wbp = $monday->copy()->setTime(12, 0);
        $match = $this->createMatchRow($div, $round, $receiver, $this->byeTeam, $wbp, $createdAt, $scheduleDate, $tbp);

        list($mapOne, $mapTwo) = $this->twoDistinctMaps();
        foreach ([$mapOne, $mapTwo] as $map) {
            $game = new Game();
            $game->match_id = $match->id;
            $game->map_id = $map->id;
            $game->team_one_id = $receiver->id;
            $game->team_two_id = $this->byeTeam->id;
            $game->winner_id = $receiver->id;
            $game->save();
        }

        $match = HlMatch::with('teams', 'games')->find($match->id);
        $match->determineWinnerAndSave();
        $this->backdatePlayedTimeline($match);

        DB::table('rikki_heroeslounge_team_division')
            ->where('team_id', $receiver->id)->where('div_id', $div->id)
            ->increment('free_win_count');
        DB::table('rikki_heroeslounge_team_division')
            ->where('team_id', $receiver->id)->where('div_id', $div->id)
            ->update(['bye' => 1]);

        $this->byesGiven[$div->id][$receiver->id] = true;
        $this->wins[$div->id][$receiver->id]++;
        $this->mapScore[$div->id][$receiver->id] += 2;
        $this->counts['byes']++;
        $this->counts['played']++;
    }

    /** Backdate the Match.Played timeline entry to the match wbp. */
    protected function backdatePlayedTimeline($match)
    {
        $ids = $match->timeline()->where('type', 'Match.Played')->get()->pluck('id')->all();
        if ($ids && $match->wbp) {
            DB::table('rikki_heroeslounge_timeline')->whereIn('id', $ids)
                ->update(['created_at' => $match->wbp, 'updated_at' => $match->wbp]);
        }
    }

    protected function twoDistinctMaps()
    {
        $i = mt_rand(0, count($this->maps) - 1);
        do {
            $j = mt_rand(0, count($this->maps) - 1);
        } while ($j === $i);
        return [$this->maps[$i], $this->maps[$j]];
    }

    /** Europe/Amsterdam Carbon -> app-timezone DB string. */
    protected function toApp(Carbon $c)
    {
        return $c->copy()->setTimezone($this->appTz)->format('Y-m-d H:i:s');
    }
```

- [ ] **Step 2: Run the full generation** (takes a few minutes — DivisionTableFix recompute per played save). Time-of-run caveat: current-round slots span Mon–Sun of the current week, so a run late on a Sunday leaves ~0 upcoming matches (and the calendar checks would under-fill). Mid-week runs are ideal; if it is Sunday evening, note it and expect thinner "upcoming" numbers:

Run: `docker exec heroeslounge-dev-web-1 php artisan fixtures:live-data --force --skip-blog`
Expected: per-division "rounds 1-N generated" lines and a final summary with `created=117` (S30: 3 rounds × (6+8+10+6+8) = 114; NMMR3: 3), `byes=6` (only divisions 759 & 761 have odd rosters → 2 BYE matches per round × 3 rounds; NMMR3's roster is even), `played` ≈ 76 (rounds 1–2 incl. their BYEs) + the slot-dependent share of the current week, `unscheduled` = 5–12.

- [ ] **Step 3: SQL invariants**

Run (the queries are deliberately backslash-free — a `timelineable_type='Rikki\\Heroeslounge\\...'` literal gets mangled by the MSYS/argv layers on this Windows host and falsely returns 0, so the type is matched with LIKE and the count is scoped to the seeded divisions via a JOIN on the match table):
```
docker exec heroeslounge-dev-db-1 mysql -uroot -proot heroeslounge -e "
SELECT div_id, round, COUNT(*) c, SUM(is_played=1) played, SUM(wbp IS NULL) unsched, SUM(winner_id IS NULL AND is_played=1) draws
FROM rikki_heroeslounge_match WHERE div_id IN (757,758,759,760,761,762) GROUP BY div_id, round ORDER BY div_id, round;
SELECT COUNT(*) cal FROM rikki_heroeslounge_match WHERE div_id IN (757,758,759,760,761,762) AND winner_id IS NULL AND wbp >= CURDATE() AND wbp <= DATE_ADD(CURDATE(), INTERVAL 100 DAY);
SELECT td.div_id, SUM(td.win_count) wins, SUM(td.match_count) mc FROM rikki_heroeslounge_team_division td WHERE td.div_id IN (757,758,759,760,761,762) GROUP BY td.div_id;
SELECT COUNT(*) tl FROM rikki_heroeslounge_timeline t
JOIN rikki_heroeslounge_timelineables ta ON ta.timeline_id=t.id AND ta.timelineable_type LIKE '%Models%Match'
JOIN rikki_heroeslounge_match m ON m.id=ta.timelineable_id AND m.div_id IN (757,758,759,760,761,762)
WHERE t.type='Match.Played';"
```
(The dump already contains ~25k historic `Match.Played` entries for OLD matches — the JOIN scoping is what makes `tl` measure only the seeded ones. Pre-seed this query returns 0.)
Expected invariants:
- Divisions 757/760: 6 matches per round; 758: 8; 759: 10; 761: 8; 762: 3 (round 1 only). Rounds 1..2 for S30 divisions: `played = c` (all), `unsched = 0`. Round 3 (and NMMR3 round 1): `played + upcoming + unsched = c`, `unsched` 1–2.
- `cal` ≥ 10 (upcoming matches inside the calendar window).
- Pivot sums: `wins` per division = number of decided (non-draw) played matches in that division (DivisionTableFix recomputed); `mc` = 2 × played matches with a winner… **actually** `match_count` = per-team count of `is_played=1` matches in that division (fixTables), so `mc` = 2 × played (draws included; BYE matches count for the receiver + the BYE team row only if BYE! is pivoted — it is not, so BYE matches contribute 1). Sanity-check plausibility rather than exact equality.
- `tl` = number of SEEDED decided played matches (= `played` − `draws` from the first query; draws get no timeline entry), and their `created_at` ≈ match wbp values (backdated — spot check one).

- [ ] **Step 4: Idempotency**

Run the command again (`--force --skip-blog`), then re-run the first SQL query.
Expected: `cleaned=117` in the output; identical per-division/round counts; `SELECT COUNT(*) FROM rikki_heroeslounge_match WHERE deleted_at IS NOT NULL AND div_id IN (757,758,759,760,761,762)` returns **0** (forceDelete leaves no soft-deleted residue).

- [ ] **Step 5: Commit**

```bash
git -c core.fsmonitor=false add plugins/dev/fixtures/console/SeedLiveData.php
git -c core.fsmonitor=false commit -m "feat(dev-fixtures): live-data round generation - Swiss-ish pairing, BYEs, played sim, calendar wbp spread"
```

---

### Task 3: Blog phase (events category + dev posts)

**Files:**
- Modify: `plugins/dev/fixtures/console/SeedLiveData.php` (replace the `seedBlog` stub)

- [ ] **Step 1: Implement.** Replace the `seedBlog` stub with:

```php
    /**
     * Blog phase: ensure the "events" category exists (the nav links to
     * /blog/category/events; the dump only has the singular "event") and
     * upsert seeder-owned posts (slug prefix "dev-") so the blog has fresh
     * this-week content (the dump's newest real post is 2026-05-10). The
     * dump's ~439 real posts and 27 categories are untouched.
     *
     * REAL Indikator dump schema (NOT the legacy fixture-shim template):
     * `status` varchar(1) '1'=published (there is NO `published` column);
     * `featured` varchar(1) '1'/'2'; `images`/`files` jsonable text NOT NULL;
     * `related_blog`/`related_news`/`related_portfolio` text NOT NULL with no
     * default - the connection runs strict, so ALL of these must be set.
     */
    protected function seedBlog()
    {
        $events = Category::where('slug', 'events')->first();
        if (!$events) {
            $events = new Category();
            $events->name = 'Events';
            $events->slug = 'events';
            $events->save();
        }
        // First real dump category (id 1 "Inside Lounge") hosts the news posts.
        $newsCategory = Category::where('slug', '<>', 'events')->orderBy('id')->first();

        $existingPost = Blog::orderBy('id')->first();
        $author = $existingPost ? $existingPost->author_id : 1;

        $posts = [
            ['slug' => 'season-30-round-3-preview',  'cat' => 'news',   'days' => 1,  'featured' => true,
             'title' => 'Season 30: Round 3 preview',
             'summary' => 'The middle of the ladder is a bloodbath - our picks for the matches to watch this week.'],
            ['slug' => 'nmmr3-kickoff',              'cat' => 'news',   'days' => 2,  'featured' => false,
             'title' => 'Nexus MM Rumble 3 kicks off',
             'summary' => 'The third edition of our matchmaking rumble is underway with a fresh six-team open bracket.'],
            ['slug' => 'community-cup-august',       'cat' => 'events', 'days' => 4,  'featured' => false,
             'title' => 'Community Cup - August Edition',
             'summary' => 'Our monthly one-day tournament returns. Bring your five and fight for glory.'],
            ['slug' => 'caster-signups-open',        'cat' => 'events', 'days' => 6,  'featured' => false,
             'title' => 'Caster sign-ups open',
             'summary' => 'Want to cast Heroes Lounge matches on our Twitch channel? Applications are open now.'],
            ['slug' => 'division-3-spotlight',       'cat' => 'news',   'days' => 9,  'featured' => false,
             'title' => 'Meet the teams: Division 3 spotlight',
             'summary' => 'Nineteen teams, one BYE, zero mercy - a closer look at our biggest division.'],
            ['slug' => 'balance-patch-roundup',      'cat' => 'news',   'days' => 12, 'featured' => false,
             'title' => 'Mid-season balance patch roundup',
             'summary' => 'Everything that changed in the Nexus and what it means for your drafts.'],
            ['slug' => 'summer-showdown-announced',  'cat' => 'events', 'days' => 16, 'featured' => false,
             'title' => 'Summer Showdown announced',
             'summary' => 'A cross-division showmatch weekend with casters, giveaways and questionable drafts.'],
        ];

        // Upsert by seeder-owned slug: delete + recreate.
        $ownSlugs = array_map(function ($p) { return self::BLOG_SLUG_PREFIX . $p['slug']; }, $posts);
        foreach (Blog::whereIn('slug', $ownSlugs)->get() as $old) {
            $old->categories()->detach();
            $old->delete();
        }

        foreach ($posts as $spec) {
            $post = new Blog();
            $post->title = $spec['title'];
            $post->slug = self::BLOG_SLUG_PREFIX . $spec['slug'];
            $post->summary = $spec['summary'];
            $post->content = '<p>' . $spec['summary'] . '</p>'
                . '<p>This is seeded development content (fixtures:live-data) so the site has a living blog to render. '
                . 'It references the seeded Season 30 / Nexus MM Rumble 3 fixtures.</p>';
            $post->images = [];            // jsonable, NOT NULL
            $post->files = [];             // jsonable, NOT NULL
            $post->related_blog = '';      // text NOT NULL, no default
            $post->related_news = '';
            $post->related_portfolio = '';
            $post->featured = $spec['featured'] ? '1' : '2'; // Indikator convention
            $post->status = '1';           // '1' = published (no `published` column)
            $post->published_at = Carbon::now()->subDays($spec['days']);
            $post->author_id = $author;
            $post->save();
            $cat = ($spec['cat'] === 'events') ? $events : $newsCategory;
            if ($cat) {
                $post->categories()->attach($cat->id);
            }
        }

        $this->output->writeln('  blog: events category ensured, ' . count($posts) . ' dev post(s) upserted');
    }
```

- [ ] **Step 2: Run** `docker exec heroeslounge-dev-web-1 php artisan fixtures:live-data --force` (full run, blog included).
Expected: `blog: events category ensured, 7 dev post(s) upserted`.

- [ ] **Step 3: SQL check + idempotency**

Run: `docker exec heroeslounge-dev-db-1 mysql -uroot -proot heroeslounge -e "SELECT COUNT(*) total, SUM(status='1') published, SUM(slug LIKE 'dev-%') dev FROM indikator_content_blog; SELECT COUNT(*) cats FROM indikator_content_blog_categories; SELECT id, name, slug FROM indikator_content_blog_categories WHERE slug IN ('event','events');"`
Expected: `total=446` (439 dump posts + 7 dev), `dev=7`, `published=432` (the dump's 425 + 7); `cats=28` (27 dump + `events`); both the dump's `event` (id 20) and the new `events` rows present. Run the command again → identical counts (no duplicate posts/categories).

- [ ] **Step 4: Commit**

```bash
git -c core.fsmonitor=false add plugins/dev/fixtures/console/SeedLiveData.php
git -c core.fsmonitor=false commit -m "feat(dev-fixtures): live-data blog phase - events category + dev posts (upsert by slug)"
```

---

### Task 4: Live verification sweep + docs

**Files:**
- Modify: `dev/README.md` (new short section), `docs/superpowers/KNOWN-ISSUES.md` (dated addendum), `docs/superpowers/PROGRESS.md`, `docs/superpowers/NEXT-SESSION.md`

- [ ] **Step 1: HTTP sweep** (after a full `fixtures:live-data --force` run). For each URL assert HTTP 200 and the listed content signal (curl or Playwright):

| URL | Expect |
|---|---|
| `/eu-season-30/division-1` | standings rows with non-zero W; round tabs 1–3 with match cards; sidebar upcoming + timeline entries |
| `/eu-season-30/division-3` | 19-team standings; BYE matches visible in the rounds; `bye` pivot set. **`free_win_count` will read 0** — the frozen `DivisionTableFix` recompute's free-win detection is unsatisfiable and clobbers the manual increments on every later save; spec-sanctioned, do NOT chase it |
| `/calendar` | ≥ 2 future date groups with fixture rows |
| `/NMMR3` → `/NMMR3/3NMMRO` | round 1 with 3 matches |
| `/match/view/<played id>` | winner shown, per-game tabs render empty-stats state (expected) |
| `/match/view/<future id>` | scheduled time in viewer TZ |
| `/match/view/<null-wbp id>` | red "not scheduled yet" state |
| `/team/view/<seeded team slug>` | match history group for Season 30 |
| `/` (homepage) | upcoming-matches + recent-results widgets populated; blog strip leads with the dev posts (the dump's newest real post is 2026-05-10, so the seeded ones sort first) |
| `/blog` + `/blog/category/events` | 200; posts render; Events nav link no longer 404s |

Get sample ids: `SELECT id, wbp, winner_id FROM rikki_heroeslounge_match WHERE div_id=757 AND round=3;`

- [ ] **Step 2: Log hygiene**

Baseline first (repo-conventional truncate, done in prior tasks too): `docker exec heroeslounge-dev-web-1 sh -c "> storage/logs/system.log"`, run the HTTP sweep, then:
Run: `docker exec heroeslounge-dev-web-1 sh -c "grep -cE 'ERROR|exception|Fatal' storage/logs/system.log || true"`
Expected: 0 (INFO noise from Division.php:190 is known/allowed).

- [ ] **Step 3: Docs.**
  - `dev/README.md`: add a "Live-data seed (`fixtures:live-data`)" subsection near the `fixtures:seed` docs: additive/scoped/re-runnable, `--force`, `--skip-blog`, do NOT confuse with the destructive `fixtures:seed`.
  - `docs/superpowers/KNOWN-ISSUES.md`: add a dated addendum at top: severity-table rows 2/4/5/7 (sections §3.2 events-404, §4.1 calendar, §4.2 no fixtures, §4.4 blog) resolved-by-data via `fixtures:live-data`; row 6 / §4.3 (gameparticipation) still blind. The addendum must ALSO correct the stale blog claim wherever it appears — §4.4 AND the §1 facts table row "Published blog posts | 1": the dump actually has **439 posts (425 published, newest 2026-05-10)** and **27 categories** — the audit's "1 post / only Uncategorized" was wrong; the real gaps were the missing `events` category and no recent content.
  - `docs/superpowers/PROGRESS.md`: task entry + notes (facts worth carrying: Match soft-deletes → clean uses forceDelete; timeline pivot polymorphic; calendar wbp-window rule; expected runtime).
  - `docs/superpowers/NEXT-SESSION.md`: refresh launch pad (live data now available; canonical demo URLs `/eu-season-30/division-1`, `/calendar`, `/NMMR3/3NMMRO`).

- [ ] **Step 4: Commit**

```bash
git -c core.fsmonitor=false add dev/README.md docs/superpowers/
git -c core.fsmonitor=false commit -m "docs: live-data seed - README section, KNOWN-ISSUES addendum, progress/launch-pad updates"
```
