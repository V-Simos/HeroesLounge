<?php namespace Indikator\Content\Components;

use Cms\Classes\ComponentBase;
use October\Rain\Support\Collection;

/**
 * Tag list component (dev shim for Indikator.Content's tagsList).
 * The shim does not implement tags; the theme's tag cloud partial simply
 * renders its "no posts" branch.
 */
class TagsList extends ComponentBase
{
    public $posts;

    public function componentDetails()
    {
        return [
            'name'        => 'Tags List (dev shim)',
            'description' => 'Lists blog tags (empty in the dev shim).'
        ];
    }

    public function defineProperties()
    {
        return [
            'slug'           => ['title' => 'Slug', 'type' => 'string', 'default' => ''],
            'pageNumber'     => ['title' => 'Page number', 'type' => 'string', 'default' => ''],
            'postsPerPage'   => ['title' => 'Posts per page', 'type' => 'string', 'default' => '10'],
            'noPostsMessage' => ['title' => 'No posts message', 'type' => 'string', 'default' => 'No post found'],
            'sortOrder'      => ['title' => 'Sort order', 'type' => 'string', 'default' => 'name asc'],
            'postPage'       => ['title' => 'Post page', 'type' => 'string', 'default' => 'blog/post']
        ];
    }

    public function onRun()
    {
        $this->posts = $this->page['posts'] = new Collection();
        $this->page['noPostsMessage'] = $this->property('noPostsMessage');
    }
}
