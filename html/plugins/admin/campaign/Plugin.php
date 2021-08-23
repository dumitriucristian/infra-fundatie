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
            'Admin\Campaign\Components\Years' => 'years',
            'Admin\Campaign\Components\Partners' => 'partners',
            'Admin\Campaign\Components\PartnersCampaign' => 'partnerscampaign',
            'Admin\Campaign\Components\CampaignsHomepage' => 'campaignshomepage',
        ];
    }

    public function registerSettings()
    {
    }
}
