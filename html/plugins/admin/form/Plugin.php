<?php namespace Admin\Form;

use System\Classes\PluginBase;

class Plugin extends PluginBase
{
    public function registerComponents()
    {
        return [
            'Admin\Form\Components\ClientForm' => 'form',
        ];
    }

    public function registerSettings()
    {
    }
}
