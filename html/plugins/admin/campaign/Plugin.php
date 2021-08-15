<?php namespace Admin\Campaign;

use System\Classes\PluginBase;

class Plugin extends PluginBase
{
    public function registerComponents()
    {
        return [
            'Admin\Campaign\Components\Campaigns' => 'campaigns',
            'Admin\Campaign\Components\SingleCampaign' =>'campaign',
            'Admin\Campaign\Components\Sponsors' => 'sponsors',
        ];
    }

    public function registerSettings()
    {
    }
}
