<?php namespace Indikator\Content\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreateBlogTables extends Migration
{
    public function up()
    {
        Schema::create('indikator_content_blog', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('title');
            $table->string('slug')->index();
            $table->text('summary')->nullable();
            $table->longText('content')->nullable();
            $table->string('image')->nullable();
            $table->text('images')->nullable();
            $table->text('files')->nullable();
            $table->boolean('featured')->default(false);
            $table->boolean('published')->default(true);
            $table->timestamp('published_at')->nullable();
            $table->integer('author_id')->unsigned()->nullable();
            $table->timestamps();
        });

        Schema::create('indikator_content_categories', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('name');
            $table->string('slug')->index();
            $table->timestamps();
        });

        Schema::create('indikator_content_blog_category', function ($table) {
            $table->engine = 'InnoDB';
            $table->integer('blog_id')->unsigned();
            $table->integer('category_id')->unsigned();
            $table->primary(['blog_id', 'category_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('indikator_content_blog_category');
        Schema::dropIfExists('indikator_content_categories');
        Schema::dropIfExists('indikator_content_blog');
    }
}
