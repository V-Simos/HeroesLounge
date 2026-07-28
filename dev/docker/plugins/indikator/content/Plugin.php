<?php namespace Indikator\Content;

use System\Classes\PluginBase;

/**
 * DEV-ONLY compatibility shim for the marketplace plugin "Indikator.Content"
 * (Content Plus by Indikator, octobercms.com/plugin/indikator-content).
 *
 * The real plugin is marketplace-only (its GitHub repo, gergo85/oc-content, was
 * deleted) and this dev environment has no marketplace Project ID. The rikki
 * plugins hard-require Indikator.Content and the old theme renders its blog
 * through it, so this shim reimplements exactly the surface the theme and the
 * rikki plugins consume:
 *
 *   - Models\Blog     (isPublished scope, listFrontEnd scope, setUrl, getCategories,
 *                      title/slug/summary/content/image/images/files/featured/
 *                      published_at/author_id columns)
 *   - Models\Category (name/slug, setUrl, post_count)
 *   - Components: blogList, blogPage, blogCategories, tagsList
 *
 * When real marketplace access is available, delete this shim and install the
 * real plugin; the fixture data will need reseeding against its schema.
 */
class Plugin extends PluginBase
{
    public function pluginDetails()
    {
        return [
            'name'        => 'Content (dev shim)',
            'description' => 'Dev-only stand-in for the marketplace plugin Indikator.Content.',
            'author'      => 'Indikator',
            'icon'        => 'icon-newspaper-o'
        ];
    }

    public function registerComponents()
    {
        return [
            'Indikator\Content\Components\BlogList'   => 'blogList',
            'Indikator\Content\Components\BlogPage'   => 'blogPage',
            'Indikator\Content\Components\Categories' => 'blogCategories',
            'Indikator\Content\Components\TagsList'   => 'tagsList',
        ];
    }
}
