<?php namespace Admin\Campaign;

use System\Classes\PluginBase;

class Plugin extends PluginBase
{
    public function registerComponents()
    {
        return [
<<<<<<< HEAD
            'Admin\Campaign\Components\campaigns' => 'campaigns',
            'Admin\Campaign\Components\singlecampaign' =>'campaign',
            'Admin\Campaign\Components\sponsors' => 'sponsors',
            'Admin\Campaign\Components\years' => 'years',
            'Admin\Campaign\Components\partners' => 'partners',
            'Admin\Campaign\Components\partnerscampaign' => 'partnersCampaign',
            'Admin\Campaign\Components\campaignshomepage' => 'campaignsHomepage',
=======
            'admin\campaign\components\campaigns' => 'campaigns',
            'admin\campaign\components\singlecampaign' =>'campaign',
            'admin\campaign\components\sponsors' => 'sponsors',
            'admin\campaign\components\years' => 'years',
            'admin\campaign\components\partners' => 'partners',
            'admin\campaign\components\partnerscampaign' => 'partnerscampaign',
            'admin\campaign\components\campaignshomepage' => 'campaignshomepage',
>>>>>>> d5923728eadf05efc45517e8d9ab4962283e1f2b
        ];
    }

    public function registerSettings()
    {
    }
}
