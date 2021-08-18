<?php namespace Admin\Campaign\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateAdminCampaignPartnersCampaign extends Migration
{
    public function up()
    {
        Schema::create('admin_campaign_partners_campaign', function($table)
        {
            $table->engine = 'InnoDB';
            $table->integer('partner_id');
            $table->integer('campaign_id');
            $table->primary(['partner_id','campaign_id']);
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('admin_campaign_partners_campaign');
    }
}
