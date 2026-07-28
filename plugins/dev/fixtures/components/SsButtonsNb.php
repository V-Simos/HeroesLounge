<?php namespace Dev\Fixtures\Components;

use Cms\Classes\ComponentBase;

/**
 * No-op stand-in for the unobtainable social-share-buttons marketplace
 * plugin's `ssbuttonsnb` component (used on blog post / static pages).
 */
class SsButtonsNb extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name'        => 'ssbuttonsnb (dev stub)',
            'description' => 'Renders nothing; keeps pages using the component working locally.'
        ];
    }
}
