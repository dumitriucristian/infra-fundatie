<?php namespace Admin\Campaign\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateAdminCampaignPartners extends Migration
{
    public function up()
    {
        Schema::table('admin_campaign_partners', function($table)
        {
            $table->text('description');
        });
    }
    
    public function down()
    {
        Schema::table('admin_campaign_partners', function($table)
        {
            $table->dropColumn('description');
        });
    }
}
