<?php namespace Dev\Fixtures\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

/**
 * The production database has a `mmr_bound` column on divisions (the rikki
 * backend forms and Match::getMmrBoundAttribute use it) but no migration in
 * the repo ever creates it - production schema drift. Add it here so the
 * models work against a from-scratch dev database.
 */
class AddDivisionMmrBound extends Migration
{
    public function up()
    {
        if (Schema::hasTable('rikki_heroeslounge_divisions')
            && !Schema::hasColumn('rikki_heroeslounge_divisions', 'mmr_bound')) {
            Schema::table('rikki_heroeslounge_divisions', function ($table) {
                $table->integer('mmr_bound')->unsigned()->nullable();
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('rikki_heroeslounge_divisions')
            && Schema::hasColumn('rikki_heroeslounge_divisions', 'mmr_bound')) {
            Schema::table('rikki_heroeslounge_divisions', function ($table) {
                $table->dropColumn('mmr_bound');
            });
        }
    }
}
