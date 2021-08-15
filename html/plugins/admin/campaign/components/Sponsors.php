<?php namespace Admin\Campaign\Components;

use Cms\Classes\ComponentBase;
use Admin\Campaign\Traits\SingleCampaignTrait;

class Sponsors extends ComponentBase
{
    use SingleCampaignTrait;


    public $campaign;
    public $sponsors;

    public function componentDetails()
    {
        return [
            'name' => 'Display sponsors',
            'description' => 'Display sponsors for a campaign',
        ];
    }
    
    public function onRun()
    {
        $this->campaign = $this->loadCampaign($this->property('campaignType'),$this->property('year'),$this->property('name'));
        $this->sponsors = $this->campaign->sponsors;
    }
    
}