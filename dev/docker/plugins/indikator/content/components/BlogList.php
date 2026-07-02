<?php namespace Indikator\Content\Components;

use Cms\Classes\ComponentBase;
use Indikator\Content\Models\Blog;
use Indikator\Content\Models\Category;

/**
 * Post list component (dev shim for Indikator.Content's blogList).
 */
class BlogList extends ComponentBase
{
    public $posts;
    public $category;
    public $noPostsMessage;
    public $pageParam;

    public function componentDetails()
    {
        return [
            'name'        => 'Blog List (dev shim)',
            'description' => 'Lists published blog posts.'
        ];
    }

    public function defineProperties()
    {
        return [
            'pageNumber' => [
                'title' => 'Page number',
                'type'  => 'string',
                'default' => '{{ :page }}'
            ],
            'categoryFilter' => [
                'title' => 'Category filter',
                'type'  => 'string',
                'default' => ''
            ],
            'postsPerPage' => [
                'title' => 'Posts per page',
                'type'  => 'string',
                'default' => '10'
            ],
            'noPostsMessage' => [
                'title' => 'No posts message',
                'type'  => 'string',
                'default' => 'No posts found'
            ],
            'sortOrder' => [
                'title' => 'Sort order',
                'type'  => 'string',
                'default' => 'published_at desc'
            ],
            'categoryPage' => [
                'title' => 'Category page',
                'type'  => 'string',
                'default' => 'blog/category'
            ],
            'postPage' => [
                'title' => 'Post page',
                'type'  => 'string',
                'default' => 'blog/post'
            ]
        ];
    }

    public function onRun()
    {
        $this->pageParam = $this->page['pageParam'] = $this->paramName('pageNumber');
        $this->noPostsMessage = $this->page['noPostsMessage'] = $this->property('noPostsMessage');
        $this->category = $this->page['category'] = $this->loadCategory();
        $this->posts = $this->page['posts'] = $this->listPosts();
    }

    protected function loadCategory()
    {
        if (!$slug = $this->property('categoryFilter')) {
            return null;
        }

        $category = Category::where('slug', $slug)->first();

        if ($category) {
            $category->setUrl($this->property('categoryPage'), $this->controller);
        }

        return $category;
    }

    protected function listPosts()
    {
        $posts = Blog::with('categories')->listFrontEnd([
            'page'     => (int) ($this->property('pageNumber') ?: 1),
            'perPage'  => (int) $this->property('postsPerPage'),
            'sort'     => $this->property('sortOrder'),
            'category' => $this->category ? $this->category->slug : null
        ]);

        $posts->each(function ($post) {
            $post->setUrl($this->property('postPage'), $this->controller);
            $post->categories->each(function ($category) {
                $category->setUrl($this->property('categoryPage'), $this->controller);
            });
        });

        return $posts;
    }
}
