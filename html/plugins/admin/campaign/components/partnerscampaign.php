<?php namespace Admin\Campaign\Components;

use Cms\Classes\ComponentBase;
use Log;
use Admin\Campaign\Traits\SingleCampaignTrait;

class Partnerscampaign extends ComponentBase
{
    use SingleCampaignTrait;

    public $partners;

    public function componentDetails()
    {
        return [
            'name' => 'Display partners for a campaign',
            'description' => 'Display partners for a campaign',
        ];
    }
    
    public function onRun()
    {

        $campaign = $this->loadCampaign($this->property('year'),$this->property('slug'));
        if(!$campaign)
        {
            Log::error("Campania din url a fost scrisa gresit");
            return redirect('404'); 
        }
        $this->partners = $campaign->partners;
    }
    
}