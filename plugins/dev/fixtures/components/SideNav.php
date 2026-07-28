<?php namespace Dev\Fixtures\Components;

use Cms\Classes\ComponentBase;

/**
 * No-op stand-in for the `SideNav` component referenced by the theme's
 * plain-wide layout (no public source for the original plugin).
 */
class SideNav extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name'        => 'SideNav (dev stub)',
            'description' => 'Renders nothing; keeps pages using the component working locally.'
        ];
    }
}
