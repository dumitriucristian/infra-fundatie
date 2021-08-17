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
                'required' => true,
            ],
            'slug' => [
                'title' => 'Campaign slug',
                'required' => true,
            ],
            
        ];
    }

    protected function loadCampaign($year,$slug)
    {
        return Campaign::where('year',$year)->where('slug',$slug)->first();
    }
    
}
