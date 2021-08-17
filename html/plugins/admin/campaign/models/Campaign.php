<?php namespace Admin\Campaign\Models;

use Model;
use Validator;
use Carbon\Carbon;
use October\Rain\Exception\ValidationException;

/**
 * Model
 */
class Campaign extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'admin_campaign_';

    /**
     * @var array Validation rules
     */
    public $rules = [
    ];

    /* Relations */
    public $attachOne = [
        'picture' => 'System\Models\File',
    ];

    public $belongsTo = [
        'campaign_type' => 'Admin\Campaign\Models\CampaignType',
    ];

    public $belongsToMany = [
        'sponsors' => [
            'Admin\Campaign\Models\Sponsor',
            'table' => 'admin_campaign_sponsors_campaigns',
            'order' => 'name',
        ],
    ];

    public function beforeCreate()
    {
        if(self::where('year',$this->year)->where('slug',$this->slug)->first())
            throw new ValidationException(['year' => "Exista deja un an la fel pentru slug-ul selectat"]);
    }
    
    public function beforeUpdate()
    {
        if(self::where('year',$this->year)->where('slug',$this->slug)->first())
            if(self::where('year',$this->year)->where('slug',$this->slug)->first()->id != $this->id)
                throw new ValidationException(['year' => "Exista deja un an la fel pentru slug-ul selectat"]);
    }

    public function getPercentageMoney() :int
    {
        return $this->raised_money*100 / $this->target_money;
    }

    public function getExpireDateCarbon()
    {
        return new Carbon($this->expire_at);
    }

    public function getMonth() :string
    {
        $date = $this->getExpireDateCarbon();
        switch ($date->month) {
            case 1:
                return "Ianuarie";
                break;

            case 2:
                return "Februarie";
                break;

            case 3:
                return "Martie";
                break;

            case 4:
                return "Aprilie";
                break;

            case 5:
                return "Mai";
                break;

            case 6:
                return "Iunie";
                break;

            case 7:
                return "Iulie";
                break;

            case 8:
                return "August";
                break;

            case 9:
                return "Septembrie";
                break;

            case 10:
                return "Octombrie";
                break;

            case 11:
                return "Noiembrie";
                break;

            case 12: 
                return "Decembrie";
                break;
        }
    }

    public function getExpireDate() :string
    {
        return $this->getExpireDateCarbon()->day . " " . $this->getMonth() . " " . $this->getExpireDateCarbon()->year;
    }

    public function urlToCampaign() :string 
    {
        return "campanie/" . $this->slug . "/" . $this->year;
    }

}
