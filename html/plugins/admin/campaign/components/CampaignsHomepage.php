<?php namespace Admin\Campaign\Components;

use Cms\Classes\ComponentBase;
use Admin\Campaign\Models\Campaign;

class CampaignsHomepage extends ComponentBase
{
    public $campaigns;

    public function componentDetails()
    {
        return [
            'name' => 'Campaign List Homepage',
            'description' => 'A list of campaigns for the homepage',
        ];
    }

    public function onRun()
    {
        $this->campaigns = Campaign::where('homepage',true)->get();
    }
    
}