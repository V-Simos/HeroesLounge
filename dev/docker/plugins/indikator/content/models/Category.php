<?php namespace Indikator\Content\Models;

use Model;

/**
 * Blog category model (dev shim for Indikator.Content).
 */
class Category extends Model
{
    use \October\Rain\Database\Traits\Sluggable;

    // Real Indikator.Content schema (from the production dump): categories live
    // in `indikator_content_blog_categories`, joined via
    // `indikator_content_blog_relations(blog_id, blog_categories_id)`.
    public $table = 'indikator_content_blog_categories';

    protected $slugs = ['slug' => 'name'];

    protected $fillable = ['name', 'slug'];

    public $belongsToMany = [
        'posts' => [
            'Indikator\Content\Models\Blog',
            'table'    => 'indikator_content_blog_relations',
            'key'      => 'blog_categories_id',
            'otherKey' => 'blog_id'
        ],
        'posts_count' => [
            'Indikator\Content\Models\Blog',
            'table'    => 'indikator_content_blog_relations',
            'key'      => 'blog_categories_id',
            'otherKey' => 'blog_id',
            'count'    => true
        ]
    ];

    /**
     * URL of the category, set by setUrl().
     */
    public $url;

    public function setUrl($pageName, $controller)
    {
        return $this->url = $controller->pageUrl($pageName, ['slug' => $this->slug]);
    }

    public function getPostCountAttribute()
    {
        return $this->posts()->isPublished()->count();
    }

    /**
     * The theme's blogCategories partial checks for nested categories;
     * the shim does not support nesting.
     */
    public function getChildrenAttribute()
    {
        return null;
    }
}
