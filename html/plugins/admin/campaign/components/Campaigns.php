<?php namespace Admin\Campaign\Components;

use Cms\Classes\ComponentBase;
use Admin\Campaign\Models\Campaign;

class Campaigns extends ComponentBase
{
    public $campaigns;

    public function componentDetails()
    {
        return [
            'name' => 'Campaign List',
            'description' => 'A list of campaigns',
        ];
    }

    public function init()
    {
        $this->addComponent('Admin\Campaign\Components\SingleCampaign', 'singleCampaign',['year','campaign_type']);
    }

    public function onRun()
    {
        $this->campaigns = Campaign::all();
    }
    
}