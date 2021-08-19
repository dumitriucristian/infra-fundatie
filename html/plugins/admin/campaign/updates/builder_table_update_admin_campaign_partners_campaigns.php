<?php namespace Admin\Campaign\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateAdminCampaignPartnersCampaigns extends Migration
{
    public function up()
    {
        Schema::rename('admin_campaign_partners_campaign', 'admin_campaign_partners_campaigns');
    }
    
    public function down()
    {
        Schema::rename('admin_campaign_partners_campaigns', 'admin_campaign_partners_campaign');
    }
}
