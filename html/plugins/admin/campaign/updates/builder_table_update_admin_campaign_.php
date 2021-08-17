<?php namespace Admin\Campaign\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateAdminCampaign extends Migration
{
    public function up()
    {
        Schema::table('admin_campaign_', function($table)
        {
            $table->boolean('finalized');
            $table->integer('target_money')->nullable()->change();
        });
    }
    
    public function down()
    {
        Schema::table('admin_campaign_', function($table)
        {
            $table->dropColumn('finalized');
            $table->integer('target_money')->nullable(false)->change();
        });
    }
}
