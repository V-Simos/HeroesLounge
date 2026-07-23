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
