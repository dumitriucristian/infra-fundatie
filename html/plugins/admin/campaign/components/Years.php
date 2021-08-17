<?php namespace Admin\Campaign\Components;

use Cms\Classes\ComponentBase;
use Request;
use Admin\Campaign\Traits\SingleCampaignTrait;
use Admin\Campaign\Models\Campaign;

class Years extends ComponentBase
{
    use SingleCampaignTrait;

    public $campaigns;

    public function componentDetails()
    {
        return [
            'name' => 'Display campaigns from other years',
            'description' => 'Display campaigns from other years',
        ];
    }
    
    public function onRun()
    {
        $campaign = $this->loadCampaign($this->property('year'),$this->property('slug'));
        if(!$campaign)
            return redirect('404');  
        $this->campaigns = Campaign::where('campaign_type_id',$campaign->campaign_type_id)->where('year','!=',$campaign->year)->get();
    }
    
}