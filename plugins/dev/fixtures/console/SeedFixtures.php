<?php namespace Dev\Fixtures\Console;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Schema;
use Symfony\Component\Console\Input\InputOption;

use RainLab\User\Models\User;
use Rikki\Heroeslounge\Models\Region;
use Rikki\Heroeslounge\Models\Season;
use Rikki\Heroeslounge\Models\Division;
use Rikki\Heroeslounge\Models\Team;
use Rikki\Heroeslounge\Models\Sloth;
use Rikki\Heroeslounge\Models\Match as HlMatch;
use Rikki\Heroeslounge\Models\Playoff;
use Rikki\Heroeslounge\Models\Game;
use Rikki\Heroeslounge\Models\Map;
use Rikki\Heroeslounge\Models\Twitchchannel;
use Indikator\Content\Models\Blog;
use Indikator\Content\Models\Category;

/**
 * DEV-ONLY: seeds the local database with fixture data.
 *
 * Idempotency: every run first TRUNCATES all fixture-owned tables (teams,
 * seasons, matches, users/sloths, blog, ...) and reseeds from scratch, so it
 * is always safe to re-run. Never point this at a database whose contents
 * you care about (e.g. the real team dump).
 */
class SeedFixtures extends Command
{
    protected $name = 'fixtures:seed';

    protected $description = 'DEV ONLY - wipe fixture-owned tables and reseed dev fixture data.';

    const PASSWORD = 'dev12345';

    /** @var array [key => Sloth] */
    protected $sloths = [];

    /** @var array [key => Team] */
    protected $teams = [];

    /** @var array [key => Division] */
    protected $divisions = [];

    /** @var Season */
    protected $season;

    public function handle()
    {
        if (!$this->option('force')) {
            $confirmed = $this->confirm(
                'This TRUNCATES all fixture-owned tables (teams, seasons, divisions, matches, '
                . 'games, maps, users/sloths, twitch channels, timeline, blog) and reseeds them. Continue?'
            );
            if (!$confirmed) {
                $this->output->writeln('<comment>Aborted - nothing was changed. Use --force to skip this prompt.</comment>');
                return 1;
            }
        }

        $this->output->writeln('<info>Seeding HeroesLounge dev fixtures...</info>');

        $this->wipe();
        $this->disableExternalModelEvents();
        $this->warmThemeData();
        $this->resetBackendAdmin();

        $this->seedRegions();
        $this->seedMaps();
        $this->seedUsersAndSloths();
        $this->seedSeasonAndDivisions();
        $this->seedTeams();
        $this->seedMatches();
        $this->variedStandings();
        $this->seedPlayoffs();
        $this->seedBlog();

        $this->output->writeln('');
        $this->output->writeln('<info>Done. Fixture accounts (frontend, password "' . self::PASSWORD . '"):</info>');
        foreach ($this->sloths as $sloth) {
            $user = $sloth->user;
            $this->output->writeln(sprintf('  %-14s %s', $user->username, $user->email));
        }
        $this->output->writeln('<info>Backend: ' . config('app.url') . '/backend - login "admin", password "' . self::PASSWORD . '".</info>');
    }

    /**
     * Console options (Laravel 6 $name-style command).
     */
    protected function getOptions()
    {
        return [
            ['force', 'f', InputOption::VALUE_NONE, 'Skip the destructive-truncate confirmation prompt (for scripted use).'],
        ];
    }

    /**
     * Truncate every table this seeder owns. This is the idempotency
     * strategy: re-running the command always starts from a blank slate.
     */
    protected function wipe()
    {
        $tables = [
            // rikki
            'rikki_heroeslounge_regions',
            'rikki_heroeslounge_seasons',
            'rikki_heroeslounge_divisions',
            'rikki_heroeslounge_teams',
            'rikki_heroeslounge_sloths',
            'rikki_heroeslounge_sloth_team',
            'rikki_heroeslounge_team_division',
            'rikki_heroeslounge_season_team',
            'rikki_heroeslounge_season_freeagent',
            'rikki_heroeslounge_match',
            'rikki_heroeslounge_team_match',
            'rikki_heroeslounge_playoffs',
            'rikki_heroeslounge_team_playoff',
            'rikki_heroeslounge_match_caster',
            'rikki_heroeslounge_match_channel',
            'rikki_heroeslounge_games',
            'rikki_heroeslounge_maps',
            'rikki_heroeslounge_twitchchannel',
            'rikki_heroeslounge_timeline',
            'rikki_heroeslounge_timelineables',
            // frontend users
            'users',
            // blog (Indikator.Content dev shim)
            'indikator_content_blog',
            'indikator_content_categories',
            'indikator_content_blog_category',
        ];

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        try {
            foreach ($tables as $table) {
                if (Schema::hasTable($table)) {
                    DB::table($table)->truncate();
                }
            }
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        $this->output->writeln('  - wiped fixture tables');
    }

    /**
     * Sloth model events call external APIs on create/save (Discord roles,
     * HeroesProfile MMR); flush its event listeners so seeding is offline-safe
     * and fast. Match/Game/Team events are kept on purpose - they maintain
     * team_score / win_count / match_count via the real production code paths.
     */
    protected function disableExternalModelEvents()
    {
        new Sloth(); // force the model to boot so there are listeners to flush
        Sloth::flushEventListeners();
    }

    /**
     * Initialise the theme customisation row (cms_theme_data) with the
     * theme.yaml defaults. October creates it lazily during the first page
     * request, and that first request 500s because the CSS combiner sees an
     * empty "color" setting; seeding it up-front avoids the one-time error.
     */
    protected function warmThemeData()
    {
        if (\Cms\Classes\Theme::exists('HeroesLounge-Theme')) {
            \Cms\Models\ThemeData::forTheme(\Cms\Classes\Theme::load('HeroesLounge-Theme'));
        }
    }

    /**
     * October generates a random password for the seeded "admin" backend user;
     * reset it to the well-known dev password.
     */
    protected function resetBackendAdmin()
    {
        $admin = \Backend\Models\User::where('login', 'admin')->first();
        if ($admin) {
            $admin->password = self::PASSWORD;
            $admin->password_confirmation = self::PASSWORD;
            $admin->save();
            $this->output->writeln('  - backend admin password reset to "' . self::PASSWORD . '"');
        }
    }

    protected function seedRegions()
    {
        foreach ([1 => 'EU', 2 => 'NA'] as $id => $title) {
            DB::table('rikki_heroeslounge_regions')->insert(['id' => $id, 'title' => $title]);
        }
        $this->output->writeln('  - regions: EU, NA');
    }

    protected function seedMaps()
    {
        // Map id 1 is special: the codebase treats games on map 1 as "free wins"
        // (see Rikki\Heroeslounge\classes\hotfixes\DivisionTableFix).
        $maps = [
            'Free Win', 'Cursed Hollow', 'Dragon Shire', 'Sky Temple',
            'Towers of Doom', 'Infernal Shrines', 'Battlefield of Eternity',
            'Tomb of the Spider Queen', 'Alterac Pass', 'Volskaya Foundry',
            'Braxis Holdout', 'Garden of Terror', 'Hanamura Temple',
        ];
        foreach ($maps as $title) {
            $map = new Map();
            $map->title = $title;
            $this->setIfColumn($map, 'enabled', 1);
            $this->setIfColumn($map, 'translations', '');
            $this->saveRow($map);
        }
        $this->output->writeln('  - maps: ' . count($maps));
    }

    protected function seedUsersAndSloths()
    {
        $people = [
            // key            username        region  battletag
            'captain_alpha' => ['AlphaCap',       1, 'AlphaCap#1234'],
            'alpha_two'     => ['SlothRider',     1, 'SlothRider#2345'],
            'captain_long'  => ['LongCap',        1, 'LongCap#3456'],
            'long_two'      => ['WallOfText',     1, 'WallOfText#4567'],
            'captain_uni'   => ['ÜnicödeUser',    1, 'Ünicöde#5678'],
            'captain_dis'   => ['DisbandedDan',   1, 'DisbandedDan#6789'],
            'captain_bye'   => ['ByeByeBart',     1, 'ByeByeBart#7890'],
            'captain_free'  => ['FreeWinFred',    1, 'FreeWinFred#8901'],
            'captain_ghost' => ['GhostGwen',      1, 'GhostGwen#9012'],
            'captain_na'    => ['NancyNA',        2, 'NancyNA#1122'],
            'captain_solo'  => ['SoloSam',        1, 'SoloSam#2233'],
            // DoubleDuty: on two teams, captain of one (The B Team)
            'double_duty'   => ['DoubleDuty',     1, 'DoubleDuty#3344'],
            'caster_ok'     => ['CasterCarl',     1, 'CasterCarl#4455'],
            'caster_wait'   => ['PendingPete',    1, 'PendingPete#5566'],
        ];

        foreach ($people as $key => list($username, $regionId, $battleTag)) {
            $user = new User();
            $user->name = $username;
            $user->surname = 'Fixture';
            $user->username = $username;
            $user->email = strtolower(preg_replace('/[^a-z0-9]/i', '', $username)) . '@dev.local';
            $user->password = self::PASSWORD;
            $user->password_confirmation = self::PASSWORD;
            $user->is_activated = true;
            $user->activated_at = Carbon::now();
            // Added by ShahiemSeymor.Roles, NOT NULL without a default:
            $this->setIfColumn($user, 'primary_usergroup', 0);
            $user->save();

            $sloth = new Sloth();
            $sloth->user_id = $user->id;
            $sloth->battle_tag = $battleTag;
            $sloth->discord_tag = $username . '#0001';
            $sloth->region_id = $regionId;
            // NOT NULL columns without defaults:
            $this->setIfColumn($sloth, 'title', $username);
            $this->setIfColumn($sloth, 'mmr', 0);
            $this->setIfColumn($sloth, 'all_mmr', 0);
            $this->setIfColumn($sloth, 'timezone', 'Europe/Berlin');
            $this->setIfColumn($sloth, 'discord_id', '');
            $this->setIfColumn($sloth, 'server_preference', '');
            $this->setIfColumn($sloth, 'newsletter_subscription', 0);
            // varied MMR so team ratings differ
            $this->setIfColumn($sloth, 'heroesprofile_mmr', 2000 + (crc32($username) % 1200));
            $this->setIfColumn($sloth, 'birthday', '1990-06-15');
            $this->setIfColumn($sloth, 'short_description', 'Fixture sloth for local development.');
            $this->saveRow($sloth);

            $this->sloths[$key] = $sloth;
        }

        $this->output->writeln('  - users + sloths: ' . count($people) . ' (password "' . self::PASSWORD . '")');
    }

    protected function seedSeasonAndDivisions()
    {
        $season = new Season();
        $season->title = 'Season 30';
        $season->slug = 'season-30';
        $season->round_length = 7;
        $season->current_round = 3;
        $season->is_active = 1;
        $season->region_id = 1; // EU
        $this->setIfColumn($season, 'type', 1);
        $this->setIfColumn($season, 'reg_open', 0);
        $this->setIfColumn($season, 'mm_active', 0);
        $this->saveRow($season);
        $this->season = $season;

        $divisionSpecs = [
            'div1' => ['Division 1', 'division-1', 2800],
            'div2' => ['Division 2', 'division-2', 2400],
            'div3' => ['Division 3', 'division-3', 2000],
        ];
        foreach ($divisionSpecs as $key => list($title, $slug, $mmrBound)) {
            $division = new Division();
            $division->title = $title;
            $division->slug = $slug;
            $division->season_id = $season->id;
            $this->setIfColumn($division, 'mmr_bound', $mmrBound);
            $this->saveRow($division);
            $this->divisions[$key] = $division;
        }

        $this->output->writeln('  - season "Season 30" (active, EU, current round 3) with 3 divisions');
    }

    protected function seedTeams()
    {
        $specs = [
            // key => [title, slug, region, division, disbanded, captain, members]
            'alpha' => [
                'Alpha Sloths', 'alpha-sloths', 1, 'div1', false,
                'captain_alpha', ['alpha_two', 'double_duty'],
            ],
            'long' => [
                'The Extraordinarily Long Team Name That Absolutely Ruins Every Layout It Touches', 'long-name', 1, 'div1', false,
                'captain_long', ['long_two'],
            ],
            'unicode' => [
                'Team Ünïcödé "Quotes" & <Ampersands>', 'team-unicode', 1, 'div1', false,
                'captain_uni', [],
            ],
            'disbanded' => [
                'Disbanded Legends', 'disbanded-legends', 1, 'div2', true,
                'captain_dis', [],
            ],
            'bye' => [
                'Bye Week Bandits', 'bye-week-bandits', 1, 'div2', false,
                'captain_bye', [],
            ],
            'freewin' => [
                'Free Win Fekkers', 'free-win-fekkers', 1, 'div2', false,
                'captain_free', [],
            ],
            'ghost' => [
                'Inactive Ghosts', 'inactive-ghosts', 1, 'div2', false,
                'captain_ghost', [],
            ],
            'na' => [
                'North Amerisloths', 'north-amerisloths', 2, 'div3', false,
                'captain_na', [],
            ],
            'solo' => [
                'Solo Sadness', 'solo-sadness', 1, 'div3', false,
                'captain_solo', [],
            ],
            'bteam' => [
                'The B Team', 'the-b-team', 1, 'div3', false,
                'double_duty', ['caster_ok', 'caster_wait'],
            ],
        ];

        foreach ($specs as $key => list($title, $slug, $regionId, $divKey, $disbanded, $captainKey, $memberKeys)) {
            $team = new Team();
            $team->title = $title;
            $team->slug = $slug;
            $team->region_id = $regionId;
            $this->setIfColumn($team, 'short_description', 'Fixture team for local development.');
            // NOT NULL columns without defaults:
            foreach (['facebook_url', 'twitch_url', 'twitter_url', 'youtube_url', 'website_url', 'server_preference'] as $column) {
                $this->setIfColumn($team, $column, '');
            }
            $this->setIfColumn($team, 'accepting_apps', 0);
            $this->setIfColumn($team, 'disbanded', 0);
            $this->setIfColumn($team, 'slothrating', 0);
            $this->saveRow($team);

            // No team gets a logo on purpose - the theme must cope with missing logos.

            if ($disbanded) {
                // Set via update so the model's timeline hook fires like production.
                $team->disbanded = 1;
                $this->saveRow($team);
            }

            $team->sloths()->attach($this->sloths[$captainKey]->id, ['is_captain' => 1]);
            foreach ($memberKeys as $memberKey) {
                $team->sloths()->attach($this->sloths[$memberKey]->id, ['is_captain' => 0]);
            }

            $team->divisions()->attach($this->divisions[$divKey]->id, [
                'win_count' => 0,
                'match_count' => 0,
                'bye' => 0,
                'free_win_count' => 0,
                'active' => 1,
            ]);
            $this->season->teams()->attach($team->id);

            $this->teams[$key] = $team;
        }

        $this->output->writeln('  - teams: ' . count($specs) . ' (incl. disbanded, long/unicode names, single-member)');
    }

    protected function seedMatches()
    {
        $d1 = $this->divisions['div1']->id;
        $d2 = $this->divisions['div2']->id;
        $d3 = $this->divisions['div3']->id;

        // --- Played matches (with games: maps, winners; scores maintained by Game events) ---
        // map ids = 1-based position in the seedMaps() list (tables are truncated
        // each run, so auto-increment ids restart at 1 in insertion order).
        $this->createMatch($d1, 1, 'alpha', 'long', Carbon::now()->subDays(14), [
            ['map' => 2, 'winner' => 'alpha'],
            ['map' => 3, 'winner' => 'alpha'],
        ]);
        $this->createMatch($d1, 1, 'unicode', 'alpha', Carbon::now()->subDays(13), [
            ['map' => 4, 'winner' => 'unicode'],
            ['map' => 5, 'winner' => 'alpha'],
            ['map' => 6, 'winner' => 'alpha'],
        ]);
        $this->createMatch($d1, 2, 'long', 'unicode', Carbon::now()->subDays(6), [
            ['map' => 7, 'winner' => 'long'],
            ['map' => 8, 'winner' => 'unicode'],
            ['map' => 2, 'winner' => 'long'],
        ]);
        // Free win: two games on map id 1 (see DivisionTableFix).
        $this->createMatch($d2, 1, 'freewin', 'disbanded', Carbon::now()->subDays(12), [
            ['map' => 1, 'winner' => 'freewin'],
            ['map' => 1, 'winner' => 'freewin'],
        ]);
        $this->createMatch($d2, 2, 'ghost', 'bye', Carbon::now()->subDays(5), [
            ['map' => 9, 'winner' => 'bye'],
            ['map' => 10, 'winner' => 'ghost'],
            ['map' => 11, 'winner' => 'bye'],
        ]);
        $this->createMatch($d3, 1, 'bteam', 'na', Carbon::now()->subDays(11), [
            ['map' => 12, 'winner' => 'bteam'],
            ['map' => 13, 'winner' => 'bteam'],
        ]);

        // --- Upcoming matches (next 14 days) ---
        // wbp set + approved caster + linked Twitch channel:
        $channel = new Twitchchannel();
        $channel->title = 'HeroesLounge';
        $channel->url = 'https://www.twitch.tv/heroeslounge';
        $this->saveRow($channel);

        $m = $this->createMatch($d1, 3, 'alpha', 'unicode', Carbon::now()->addDays(2));
        $m->casters()->attach($this->sloths['caster_ok']->id, ['approved' => 1]);
        $m->channels()->attach($channel->id);

        // wbp set + pending (unapproved) caster:
        $m = $this->createMatch($d1, 3, 'long', 'alpha', Carbon::now()->addDays(5));
        $m->casters()->attach($this->sloths['caster_wait']->id, ['approved' => 0]);

        // wbp NULL (not yet scheduled):
        $this->createMatch($d1, 3, 'unicode', 'long', null);

        // More upcoming spread over the window:
        $this->createMatch($d2, 3, 'freewin', 'ghost', Carbon::now()->addDays(7));
        $this->createMatch($d2, 3, 'bye', 'disbanded', Carbon::now()->addDays(10));
        $this->createMatch($d3, 3, 'na', 'bteam', Carbon::now()->addDays(13));

        $this->output->writeln('  - matches: 6 played (with games), 6 upcoming (wbp set/null, casters, channel)');
    }

    /**
     * Creates a match; when $games are given, plays them through the real
     * model events (Game::afterSave keeps team_match.team_score, then
     * Match::determineWinnerAndSave sets the winner and is_played, and the
     * production DivisionTableFix recomputes the standings).
     */
    protected function createMatch($divId, $round, $teamOneKey, $teamTwoKey, $wbp, array $games = [])
    {
        $teamOne = $this->teams[$teamOneKey];
        $teamTwo = $this->teams[$teamTwoKey];

        $match = new HlMatch();
        $match->div_id = $divId;
        $match->round = $round;
        $match->wbp = $wbp ? $wbp->format('Y-m-d H:i:s') : null;
        $match->is_played = 0;
        $this->saveRow($match);

        $match->teams()->attach($teamOne->id, ['team_score' => 0]);
        $match->teams()->attach($teamTwo->id, ['team_score' => 0]);

        if ($games) {
            foreach ($games as $spec) {
                $game = new Game();
                $game->match_id = $match->id;
                $game->map_id = $spec['map'];
                $game->team_one_id = $teamOne->id;
                $game->team_two_id = $teamTwo->id;
                $game->winner_id = $this->teams[$spec['winner']]->id;
                $this->saveRow($game);
            }

            $match = HlMatch::with('teams', 'games')->find($match->id);
            $match->determineWinnerAndSave();
        }

        return $match;
    }

    /**
     * Push extra variety into the team_division pivots that the played
     * matches alone cannot produce (bye, inactive entry, free_win_count).
     * win_count / match_count come from the real played matches above.
     */
    protected function variedStandings()
    {
        DB::table('rikki_heroeslounge_team_division')
            ->where('team_id', $this->teams['bye']->id)
            ->update(['bye' => 1]);

        DB::table('rikki_heroeslounge_team_division')
            ->where('team_id', $this->teams['ghost']->id)
            ->update(['active' => 0]);

        // DivisionTableFix's free-win detection is buggy (it matches game ids
        // against match ids), so set the intended value explicitly.
        DB::table('rikki_heroeslounge_team_division')
            ->where('team_id', $this->teams['freewin']->id)
            ->update(['free_win_count' => 1]);

        $this->output->writeln('  - standings variety: bye, inactive division entry, free win');
    }

    /**
     * Seed knockout playoffs so the bracket-render surface (PlayoffOverview)
     * has real data: one single-elimination (se8, with a BYE) and one
     * double-elimination (de8). Both are attached to Season 30, so BOTH the
     * in-season route (/season-30/playoff/<title>) and the standalone route
     * (/tournament/<slug>) resolve. Winners are then set on the early rounds
     * through the REAL production model events (Match::afterSave advances the
     * winner to playoff_winner_next and the loser to playoff_loser_next), so a
     * team that advances re-appears in a later node — the exact condition the
     * bracket spoiler toggle's repeat-team hiding must handle.
     */
    protected function seedPlayoffs()
    {
        // The frozen Playoff::createMatches() inserts match rows without setting
        // the NOT-NULL-without-default `is_played` column; production MySQL runs
        // in a non-strict sql_mode that coerces the missing value to 0. The dev
        // container defaults to STRICT mode, which rejects the insert, so relax
        // it for this seeding connection (session-scoped, dev-only). Capture the
        // original mode first and restore it as the LAST statement of this
        // method so the relaxed mode never leaks into the next seed step.
        $originalSqlMode = DB::selectOne('SELECT @@SESSION.sql_mode AS m')->m;
        DB::statement("SET SESSION sql_mode=(SELECT REPLACE(REPLACE(@@sql_mode,'STRICT_TRANS_TABLES',''),'STRICT_ALL_TABLES',''))");

        // Placeholder team the frozen Playoff::seedTeams() looks up by the
        // literal title 'BYE!' whenever a seed slot is unfilled. Deliberately
        // NOT attached to any season/division, so it never leaks into
        // standings, calendars or team listings — it only exists to fill an
        // empty bracket slot. (Local placeholder only; distinct from the real
        // fixture team $this->teams['bye'] "Bye Week Bandits".)
        $byePlaceholder = Team::where('title', 'BYE!')->first();
        if (!$byePlaceholder) {
            $byePlaceholder = new Team();
            $byePlaceholder->title = 'BYE!';
            $byePlaceholder->slug = 'bye-placeholder';
            $byePlaceholder->region_id = 1;
            $this->setIfColumn($byePlaceholder, 'short_description', '');
            foreach (['facebook_url', 'twitch_url', 'twitter_url', 'youtube_url', 'website_url', 'server_preference'] as $column) {
                $this->setIfColumn($byePlaceholder, $column, '');
            }
            $this->setIfColumn($byePlaceholder, 'accepting_apps', 0);
            $this->setIfColumn($byePlaceholder, 'disbanded', 0);
            $this->setIfColumn($byePlaceholder, 'slothrating', 0);
            $this->saveRow($byePlaceholder);
        }

        $tz = 'Europe/Berlin';
        // A near-future kickoff so unplayed nodes render a scheduled date/time.
        $when = Carbon::now()->addDays(7);

        // Ordered pool of the 10 real fixture teams (seed 1 = first inserted).
        // Seeds 2/3 are the extreme-long-name and unicode teams on purpose —
        // free layout stress-test for the fixed 13rem node box.
        $pool = array_values($this->teams);

        // ---- Single elimination: se8, 7 real teams + 1 BYE (seed 8 empty) ----
        $se = new Playoff();
        $se->title = 'Season 30 Playoffs';
        $se->slug = 'season-30-playoffs';
        $se->type = 'se8';
        $se->season_id = $this->season->id;
        $se->region_id = 1;
        $this->setIfColumn($se, 'reg_open', 0);
        $this->saveRow($se);
        for ($seed = 1; $seed <= 7; $seed++) {
            $se->teams()->attach($pool[$seed - 1]->id, ['seed' => $seed]);
        }
        $se->createMatches($when->year, $when->month, $when->day, $tz);
        $se->seedTeams(); // seed 8 -> BYE!; its round-1 match auto-resolves

        // Resolve the remaining round-1 matches, then one semifinal, so winners
        // propagate into round 2 and the final (repeat-team appearances).
        $this->playPlayoffRound($se, 1, 1, 4);
        $this->playPlayoffMatch($this->playoffMatchAt($se, 1, 2, 1));

        // ---- Double elimination: de8, full 8 teams ----
        // de8 fills all 8 seed slots straight from the fixture pool
        // ($pool[0..7] below), so it is hard-coupled to a >=8-team fixture set.
        // Make that coupling explicit: with fewer than 8 teams the indexing
        // would fatal cryptically, so skip ONLY de8 (the se8 above needs 7).
        if (count($pool) >= 8) {
            $de = new Playoff();
            $de->title = 'Community Cup';
            $de->slug = 'community-cup';
            $de->type = 'de8';
            $de->season_id = $this->season->id;
            $de->region_id = 1;
            $this->setIfColumn($de, 'reg_open', 0);
            $this->saveRow($de);
            for ($seed = 1; $seed <= 8; $seed++) {
                $de->teams()->attach($pool[$seed - 1]->id, ['seed' => $seed]);
            }
            $de->createMatches($when->year, $when->month, $when->day, $tz);
            $de->seedTeams();

            // Upper round 1: winners -> upper R2, losers dropped into lower R1.
            $this->playPlayoffRound($de, 1, 1, 4);
            // Lower round 1 (now populated by the dropped losers): exercises the
            // bracket-losers spoiler special-case + more repeat-team appearances.
            $this->playPlayoffRound($de, 2, 1, 2);

            $this->output->writeln(
                '  - playoffs: se8 "' . $se->title . '" (id ' . $se->id . ', slug ' . $se->slug . ', +BYE), '
                . 'de8 "' . $de->title . '" (id ' . $de->id . ', slug ' . $de->slug . ')'
            );
        } else {
            $this->output->writeln('<comment>seedPlayoffs: <8 fixture teams; skipping de8.</comment>');
            $this->output->writeln(
                '  - playoffs: se8 "' . $se->title . '" (id ' . $se->id . ', slug ' . $se->slug . ', +BYE)'
            );
        }

        // Restore the sql_mode captured at the top so the relaxed (non-strict)
        // session mode never leaks into whatever seed step runs after playoffs.
        DB::statement("SET SESSION sql_mode = ?", [$originalSqlMode]);
    }

    /**
     * Fetch a playoff match by its Cantor-encoded (bracket, round, matchnumber)
     * position, the same encoding Playoff::createMatches wrote.
     */
    protected function playoffMatchAt($playoff, $bracket, $round, $matchnumber)
    {
        $pos = HlMatch::encodePlayoffPosition($bracket, $round, $matchnumber);
        return $playoff->matches()->where('playoff_position', $pos)->first();
    }

    /** Resolve matchnumbers 1..$count of a (bracket, round). */
    protected function playPlayoffRound($playoff, $bracket, $round, $count)
    {
        for ($mn = 1; $mn <= $count; $mn++) {
            $this->playPlayoffMatch($this->playoffMatchAt($playoff, $bracket, $round, $mn));
        }
    }

    /**
     * Give a playoff match a 2:1 result for its first-listed team via the real
     * production path: setting winner_id and saving fires Match::afterSave,
     * which advances the winner (and, in double-elim, the loser) into the next
     * node. Skips nodes that are still TBD or already decided (e.g. a BYE).
     */
    protected function playPlayoffMatch($match)
    {
        if (!$match) {
            return;
        }
        $match = HlMatch::with('teams')->find($match->id);
        if ($match->teams->count() < 2 || $match->winner_id) {
            return;
        }
        $winner = $match->teams[0];
        $loser = $match->teams[1];
        $match->teams()->updateExistingPivot($winner->id, ['team_score' => 2]);
        $match->teams()->updateExistingPivot($loser->id, ['team_score' => 1]);
        $match->winner_id = $winner->id;
        $this->saveRow($match);
    }

    protected function seedBlog()
    {
        // Look the admin up by login (like resetBackendAdmin()) instead of
        // assuming it has id 1.
        $admin = \Backend\Models\User::where('login', 'admin')->first();

        // Second backend user so posts have varied authors (idempotent:
        // backend_users is not truncated by wipe()).
        $editor = \Backend\Models\User::where('login', 'editor')->first();
        if (!$editor) {
            $editor = new \Backend\Models\User();
            $editor->login = 'editor';
            $editor->email = 'editor@dev.local';
            $editor->first_name = 'Eddie';
            $editor->last_name = 'Editor';
            $editor->password = self::PASSWORD;
            $editor->password_confirmation = self::PASSWORD;
            $editor->is_activated = true;
            $editor->save();
        }

        $events = new Category();
        $events->name = 'events';
        $events->slug = 'events';
        $events->save();

        $announcements = new Category();
        $announcements->name = 'announcements';
        $announcements->slug = 'announcements';
        $announcements->save();

        $posts = [
            [
                'title' => 'Season 30 is live!',
                'summary' => 'Sign-ups are closed and the first rounds are underway across all divisions.',
                'category' => $announcements,
                'days_ago' => 16,
                'featured' => true,
            ],
            [
                'title' => 'Community Cup - July Edition',
                'summary' => 'Our monthly one-day tournament returns. Bring your five and fight for glory.',
                'category' => $events,
                'days_ago' => 9,
                'featured' => false,
            ],
            [
                'title' => 'Caster sign-ups open for Season 30',
                'summary' => 'Want to cast Heroes Lounge matches on our Twitch channel? Apply now.',
                'category' => $events,
                'days_ago' => 6,
                'featured' => false,
            ],
            [
                'title' => 'Mid-season balance patch roundup',
                'summary' => 'Everything that changed in the Nexus and what it means for your drafts.',
                'category' => $announcements,
                'days_ago' => 3,
                'featured' => false,
            ],
            [
                'title' => 'Meet the teams: Division 1 spotlight',
                'summary' => 'A closer look at the squads battling at the top of the ladder this season.',
                'category' => $events,
                'days_ago' => 1,
                'featured' => false,
            ],
        ];

        foreach ($posts as $i => $spec) {
            $post = new Blog();
            $post->title = $spec['title'];
            $post->summary = $spec['summary'];
            $post->content = '<p>' . $spec['summary'] . '</p><p>This is fixture content for local development. '
                . 'It exists so the old theme has something to render while the UI rework is in progress.</p>';
            $post->featured = $spec['featured'];
            $post->published = true;
            $post->published_at = Carbon::now()->subDays($spec['days_ago']);
            // Alternate authors: the "admin" and "editor" backend users.
            $post->author_id = ($i % 2 === 0) ? $admin->id : $editor->id;
            $post->save();
            $post->categories()->attach($spec['category']->id);
        }

        $this->output->writeln('  - blog: 2 categories (events, announcements), ' . count($posts) . ' posts');
    }

    /**
     * Sets an attribute only when the column actually exists - the rikki
     * migrations have drifted from production in places, so the seeder
     * stays defensive about optional columns.
     */
    protected function setIfColumn($model, $column, $value)
    {
        if (Schema::hasColumn($model->getTable(), $column)) {
            $model->$column = $value;
        }
    }

    /**
     * Saves bypassing October's Validation trait rules where present
     * (fixture rows are deliberately ugly; e.g. team names with <> chars).
     */
    protected function saveRow($model)
    {
        if (property_exists($model, 'rules')) {
            $model->rules = [];
        }
        $model->save();
    }
}
