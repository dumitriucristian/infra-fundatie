<?php namespace Admin\Campaign\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateAdminCampaignSponsorsCampaigns extends Migration
{
    public function up()
    {
        Schema::create('admin_campaign_sponsors_campaigns', function($table)
        {
            $table->engine = 'InnoDB';
            $table->integer('sponsor_id');
            $table->integer('campaign_id');
            $table->primary(['sponsor_id','campaign_id']);
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('admin_campaign_sponsors_campaigns');
    }
}
