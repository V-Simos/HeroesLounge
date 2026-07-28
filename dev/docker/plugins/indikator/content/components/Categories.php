<?php namespace Indikator\Content\Components;

use Cms\Classes\ComponentBase;
use Indikator\Content\Models\Category;

/**
 * Category list component (dev shim for Indikator.Content's blogCategories).
 */
class Categories extends ComponentBase
{
    public $categories;
    public $currentCategorySlug;

    public function componentDetails()
    {
        return [
            'name'        => 'Blog Categories (dev shim)',
            'description' => 'Lists blog categories.'
        ];
    }

    public function defineProperties()
    {
        return [
            'slug' => [
                'title' => 'Active category slug',
                'type'  => 'string',
                'default' => '{{ :slug }}'
            ],
            'categoryPage' => [
                'title' => 'Category page',
                'type'  => 'string',
                'default' => 'blog/category'
            ]
        ];
    }

    public function onRun()
    {
        $this->currentCategorySlug = $this->page['currentCategorySlug'] = $this->property('slug');
        $this->categories = $this->page['categories'] = $this->listCategories();
    }

    protected function listCategories()
    {
        $categories = Category::orderBy('name')->get();

        $categories->each(function ($category) {
            $category->setUrl($this->property('categoryPage'), $this->controller);
        });

        return $categories;
    }
}
