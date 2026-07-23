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
 *    creates the "events" category if missing; the dump's real posts are
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
     * Wrong-DB guard: the target seasons (by slug, active), the BYE team and
     * some maps must exist. This is the WHOLE guard - matches found under the
     * target divisions are seeder-owned by definition (0 exist in the pristine
     * dump), so re-runs never abort.
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
     * with their attached timeline entries (collected BEFORE the delete -
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

        // Guard against dump-orphan pivot rows that may collide with this
        // newly assigned auto-increment id before we attach the real teams.
        $match->teams()->detach();
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

    protected function seedBlog()
    {
        // Task 3
    }
}
