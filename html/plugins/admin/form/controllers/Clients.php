<?php namespace Admin\Form\Controllers;

use Backend\Classes\Controller;
use BackendMenu;

class Clients extends Controller
{
    public $implement = [        'Backend\Behaviors\ListController',        'Backend\Behaviors\ReorderController'    ];
    
    public $listConfig = 'config_list.yaml';
    public $reorderConfig = 'config_reorder.yaml';

    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('Admin.Form', 'main-menu-item');
    }
}
