<?php 

namespace Admin\Campaign\Traits;

use Request;
use Admin\Campaign\Models\Campaign;
use Admin\Campaign\Models\CampaignType;

trait SingleCampaignTrait
{

    public function defineProperties()
    {
        return [
            'year' => [
                'title' => 'Campaign year',
                'type' => 'dropdown',
                'placeholder' => 'Select a year',
                'required' => true,
            ],
            'campaignType' => [
                'title' => 'Campaign type',
                'type' => 'dropdown',
                'placeholder' => 'Select a campaign type',
                'required' => true,
            ],
            'name' => [
                'title' => 'Campaign name',
                'type' => 'dropdown',
                'placeholder' => 'Select a campaign name',
                'required' => true,
                'depends' => ['year','campaignType'],
            ],
            
        ];
    }

    public function getYearOptions()
    {
        $values = Campaign::groupBy('year')->pluck('year')->toArray();
        return array_combine($values,$values);
    }

    public function getCampaignTypeOptions()
    {
        $valuesName = CampaignType::pluck('name')->toArray();
        $valuesId = CampaignType::pluck('id')->toArray();
        return array_combine($valuesId,$valuesName);
    }
    
    public function getNameOptions()
    {
        $year = Request::input('year');
        $campaignType = Request::input('campaignType');

        $values = Campaign::where('year',$year)->where('campaign_type_id',$campaignType)->pluck('name')->toArray();
        return array_combine($values,$values);
    }

    protected function loadCampaign($campaign_type_id,$year,$name)
    {
        return Campaign::where('campaign_type_id',$campaign_type_id)->where('year',$year)->where('name',$name)->first();
    }
    
}
