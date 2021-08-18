<?php namespace Admin\Campaign\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateAdminCampaignPartners extends Migration
{
    public function up()
    {
        Schema::create('admin_campaign_partners', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('name');
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('admin_campaign_partners');
    }
}
