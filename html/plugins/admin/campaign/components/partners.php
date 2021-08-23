<?php namespace Admin\Campaign\Components;

use Cms\Classes\ComponentBase;
use Admin\Campaign\Models\Partner;

class Partners extends ComponentBase
{
    public $partners;

    public function componentDetails()
    {
        return [
            'name' => 'Display all partners',
            'description' => 'Display partners all partners',
        ];
    }
    
    public function onRun()
    {
        $this->partners = Partner::all();
    }
    
}