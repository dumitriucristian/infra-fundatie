<?php namespace Admin\Form\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateAdminFormClient2 extends Migration
{
    public function up()
    {
        Schema::table('admin_form_client', function($table)
        {
            $table->string('income_type');
        });
    }
    
    public function down()
    {
        Schema::table('admin_form_client', function($table)
        {
            $table->dropColumn('income_type');
        });
    }
}
