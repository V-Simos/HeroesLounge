<?php namespace Dev\Fixtures\Console;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Schema;

use RainLab\User\Models\User;
use Rikki\Heroeslounge\Models\Region;
use Rikki\Heroeslounge\Models\Season;
use Rikki\Heroeslounge\Models\Division;
use Rikki\Heroeslounge\Models\Team;
use Rikki\Heroeslounge\Models\Sloth;
use Rikki\Heroeslounge\Models\Match as HlMatch;
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
        $this->output->writeln('<info>Seeding HeroesLounge dev fixtures...</info>');

        $this->wipe();
        $this->disableExternalModelEvents();
        $this->resetBackendAdmin();

        $this->seedRegions();
        $this->seedMaps();
        $this->seedUsersAndSloths();
        $this->seedSeasonAndDivisions();
        $this->seedTeams();
        $this->seedMatches();
        $this->variedStandings();
        $this->seedBlog();

        $this->output->writeln('');
        $this->output->writeln('<info>Done. Fixture accounts (frontend, password "' . self::PASSWORD . '"):</info>');
        foreach ($this->sloths as $key => $sloth) {
            $user = $sloth->user;
            $this->output->writeln(sprintf('  %-14s %s', $user->username, $user->email));
        }
        $this->output->writeln('<info>Backend: http://localhost:8090/backend - login "admin", password "' . self::PASSWORD . '".</info>');
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
        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->truncate();
            }
        }
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

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

    protected function seedBlog()
    {
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
            // Alternate authors: default admin (id 1) and the "editor" user.
            $post->author_id = ($i % 2 === 0) ? 1 : $editor->id;
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
