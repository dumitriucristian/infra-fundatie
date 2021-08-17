<?php namespace Admin\Campaign\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateAdminCampaign2 extends Migration
{
    public function up()
    {
        Schema::table('admin_campaign_', function($table)
        {
            $table->text('description');
        });
    }
    
    public function down()
    {
        Schema::table('admin_campaign_', function($table)
        {
            $table->dropColumn('description');
        });
    }
}
