<?php namespace Admin\Campaign\Components;

use Cms\Classes\ComponentBase;
use Admin\Campaign\Traits\SingleCampaignTrait;

class Partners extends ComponentBase
{
    use SingleCampaignTrait;

    public $partners;

    public function componentDetails()
    {
        return [
            'name' => 'Display partners',
            'description' => 'Display partners for a campaign',
        ];
    }
    
    public function onRun()
    {

        $campaign = $this->loadCampaign($this->property('year'),$this->property('slug'));
        if(!$campaign)
            return redirect('404');  
        $this->partners = $campaign->partners;
    }
    
}