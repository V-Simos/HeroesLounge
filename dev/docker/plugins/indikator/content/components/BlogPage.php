<?php namespace Indikator\Content\Components;

use Cms\Classes\ComponentBase;
use Indikator\Content\Models\Blog;

/**
 * Single post component (dev shim for Indikator.Content's blogPage).
 * Rikki\LoungeViews\Components\BlogPage extends this class; it relies on
 * $this->post, $this->categoryPage and $this->getPage().
 */
class BlogPage extends ComponentBase
{
    public $post;
    public $categoryPage;

    public function componentDetails()
    {
        return [
            'name'        => 'Blog Page (dev shim)',
            'description' => 'Displays a single blog post.'
        ];
    }

    public function defineProperties()
    {
        return [
            'slug' => [
                'title' => 'Slug',
                'type'  => 'string',
                'default' => '{{ :slug }}'
            ],
            'categoryPage' => [
                'title' => 'Category page',
                'type'  => 'string',
                'default' => 'blog/category'
            ],
            'redirectPage' => [
                'title' => 'Redirect page when post is not found',
                'type'  => 'string',
                'default' => ''
            ]
        ];
    }

    public function onRun()
    {
        $this->categoryPage = $this->property('categoryPage');
        $this->post = $this->page['post'] = $this->loadPost();
    }

    protected function loadPost()
    {
        $post = Blog::with('categories')
            ->isPublished()
            ->where('slug', $this->property('slug'))
            ->first();

        if (!$post) {
            return null;
        }

        $post->setUrl($this->getPage()->getBaseFileName(), $this->controller);

        $post->categories->each(function ($category) {
            $category->setUrl($this->categoryPage, $this->controller);
        });

        return $post;
    }

    /**
     * The real Indikator.Content component exposed the current CMS page object;
     * Rikki\LoungeViews\Components\BlogPage calls this.
     */
    public function getPage()
    {
        return $this->controller->getPage();
    }
}
