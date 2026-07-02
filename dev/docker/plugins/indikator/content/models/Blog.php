<?php namespace Indikator\Content\Models;

use Model;
use Carbon\Carbon;

/**
 * Blog post model (dev shim for Indikator.Content).
 * API modelled on what the HeroesLounge theme + rikki plugins consume.
 */
class Blog extends Model
{
    use \October\Rain\Database\Traits\Sluggable;

    public $table = 'indikator_content_blog';

    protected $slugs = ['slug' => 'title'];

    protected $dates = ['published_at'];

    protected $jsonable = ['images', 'files'];

    protected $fillable = [
        'title', 'slug', 'summary', 'content', 'image', 'images', 'files',
        'featured', 'published', 'published_at', 'author_id'
    ];

    public $belongsToMany = [
        'categories' => [
            'Indikator\Content\Models\Category',
            'table'    => 'indikator_content_blog_category',
            'key'      => 'blog_id',
            'otherKey' => 'category_id'
        ]
    ];

    /**
     * URL of the post, set by setUrl().
     */
    public $url;

    public function scopeIsPublished($query)
    {
        return $query
            ->where('published', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', Carbon::now());
    }

    /**
     * Frontend listing scope, RainLab.Blog-style.
     * Supported options: page, perPage, sort ('column desc'), category (slug), search.
     * Returns a paginator (theme pagination uses lastPage/currentPage).
     */
    public function scopeListFrontEnd($query, $options = [])
    {
        extract(array_merge([
            'page'     => 1,
            'perPage'  => 10,
            'sort'     => 'published_at desc',
            'category' => null,
            'search'   => ''
        ], $options));

        $query = $query->isPublished();

        if ($sort) {
            $parts = explode(' ', trim($sort));
            $column = $parts[0];
            $direction = isset($parts[1]) && strtolower($parts[1]) === 'asc' ? 'asc' : 'desc';
            if (in_array($column, ['published_at', 'created_at', 'updated_at', 'title', 'id'])) {
                $query = $query->orderBy($column, $direction);
            }
        }

        if ($category) {
            $query = $query->whereHas('categories', function ($q) use ($category) {
                $q->where('slug', $category);
            });
        }

        if (strlen($search)) {
            $query = $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('summary', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        return $query->paginate($perPage, $page);
    }

    /**
     * Sets the url attribute for this post using the given CMS page.
     */
    public function setUrl($pageName, $controller)
    {
        return $this->url = $controller->pageUrl($pageName, ['slug' => $this->slug]);
    }

    /**
     * Returns the categories as a plain array (used by OFFLINE.SiteSearch's
     * Indikator.Content provider).
     */
    public function getCategories()
    {
        return $this->categories->map(function ($category) {
            return ['name' => $category->name, 'slug' => $category->slug];
        })->all();
    }
}
