<?php namespace Dev\Fixtures;

use System\Classes\PluginBase;

/**
 * DEV-ONLY fixture plugin for the HeroesLounge local environment.
 *
 * - Registers `php artisan fixtures:seed` (idempotent fixture data seeder).
 * - Registers no-op stand-ins for theme components whose marketplace plugins
 *   are unobtainable: ssbuttonsnb / ssbuttonsssb (social share buttons) and
 *   SideNav.
 * - Loads stub AuthCode classes (production secret holders that are not in
 *   the repo) so rikki model events / backend actions do not fatal locally.
 *
 * This plugin must never be deployed to production.
 */
class Plugin extends PluginBase
{
    public $require = ['Rikki.Heroeslounge', 'Indikator.Content', 'RainLab.User'];

    public function pluginDetails()
    {
        return [
            'name'        => 'Dev Fixtures',
            'description' => 'Dev-only fixture data seeder and compatibility stubs. Do not deploy.',
            'author'      => 'Dev',
            'icon'        => 'icon-flask'
        ];
    }

    public function register()
    {
        $this->registerConsoleCommand('fixtures.seed', 'Dev\Fixtures\Console\SeedFixtures');
        $this->loadAuthCodeStubs();
    }

    public function registerComponents()
    {
        return [
            'Dev\Fixtures\Components\SsButtonsNb'  => 'ssbuttonsnb',
            'Dev\Fixtures\Components\SsButtonsSsb' => 'ssbuttonsssb',
            'Dev\Fixtures\Components\SideNav'      => 'SideNav',
        ];
    }

    /**
     * The production deployment has AuthCode classes holding API secrets
     * (Discord, HeroesProfile, Mailchimp). They are gitignored, so calls into
     * them fatal with "class not found" locally. Provide dummies that return
     * empty strings; the surrounding code degrades gracefully (failed curl
     * calls are ignored).
     */
    protected function loadAuthCodeStubs()
    {
        if (!class_exists('Rikki\Heroeslounge\classes\Discord\AuthCode')) {
            require_once __DIR__ . '/classes/authcode_stubs.php';
        }
    }
}
