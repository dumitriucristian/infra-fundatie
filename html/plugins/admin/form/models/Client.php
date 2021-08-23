<?php namespace Admin\Form\Models;

use Model;

/**
 * Model
 */
class Client extends Model
{
    use \October\Rain\Database\Traits\Validation;
    
    protected $fillable = ['firstname','lastname','email',
                            'cnp','dad_initial','phone_number',
                            'street','address_number','block',
                            'staircase','floor','apartement',
                            'county', 'city','income_type'];

    /**
     * @var string The database table used by the model.
     */
    public $table = 'admin_form_client';

    /**
     * @var array Validation rules
     */
    public $rules = [
    ];
}
