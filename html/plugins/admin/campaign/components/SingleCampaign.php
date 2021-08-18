<?php namespace Admin\Campaign\Components;

use Cms\Classes\ComponentBase;
use Request;
use Admin\Campaign\Traits\SingleCampaignTrait;
use Log;

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
        $this->campaign = $this->loadCampaign($this->property('year'),$this->property('slug'));
        if(!$this->campaign)
        {
            Log::error("Campania din url a fost scrisa gresit");
            return redirect('404');  
        }
    }
    
}