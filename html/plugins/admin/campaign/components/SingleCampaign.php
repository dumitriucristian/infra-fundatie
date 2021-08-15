<?php namespace Admin\Campaign\Components;

use Cms\Classes\ComponentBase;
use Request;
use Admin\Campaign\Traits\SingleCampaignTrait;

class SingleCampaign extends ComponentBase
{
    use SingleCampaignTrait;

    public $campaign;

    public function componentDetails()
    {
        return [
            'name' => 'A single campaign display',
            'description' => 'A single campaign display',
        ];
    }
    
    public function onRun()
    {
        $this->campaign = $this->loadCampaign($this->property('campaignType'),$this->property('year'),$this->property('name'));
    }
    
}