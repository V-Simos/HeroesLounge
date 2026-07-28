<?php namespace Dev\Fixtures\Components;

use Cms\Classes\ComponentBase;

/**
 * No-op stand-in for the unobtainable social-share-buttons marketplace
 * plugin's `ssbuttonsssb` component.
 */
class SsButtonsSsb extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name'        => 'ssbuttonsssb (dev stub)',
            'description' => 'Renders nothing; keeps pages using the component working locally.'
        ];
    }
}
