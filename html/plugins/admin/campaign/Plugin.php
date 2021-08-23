<?php namespace Admin\Campaign;

use System\Classes\PluginBase;

class Plugin extends PluginBase
{
    public function registerComponents()
    {
        return [
            'admin\campaign\components\campaigns' => 'campaigns',
            'admin\campaign\components\singlecampaign' =>'campaign',
            'admin\campaign\components\sponsors' => 'sponsors',
            'admin\campaign\components\years' => 'years',
            'admin\campaign\components\partners' => 'partners',
            'admin\campaign\components\partnerscampaign' => 'partnerscampaign',
            'admin\campaign\components\campaignshomepage' => 'campaignshomepage',
        ];
    }

    public function registerSettings()
    {
    }
}
