<?php namespace Admin\Campaign;

use System\Classes\PluginBase;

class Plugin extends PluginBase
{
    public function registerComponents()
    {
        return [
            'Admin\Campaign\Components\campaigns' => 'campaigns',
            'Admin\Campaign\Components\singlecampaign' =>'campaign',
            'Admin\Campaign\Components\sponsors' => 'sponsors',
            'Admin\Campaign\Components\years' => 'years',
            'Admin\Campaign\Components\partners' => 'partners',
            'Admin\Campaign\Components\partnerscampaign' => 'partnersCampaign',
            'Admin\Campaign\Components\campaignshomepage' => 'campaignsHomepage',
        ];
    }

    public function registerSettings()
    {
    }
}
