<?php namespace Admin\Form\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateAdminFormClient extends Migration
{
    public function up()
    {
        Schema::create('admin_form_client', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('firstname');
            $table->string('lastname');
            $table->bigInteger('cnp');
            $table->string('email');
            $table->string('phone_number');
            $table->string('dad_initial');
            $table->string('street');
            $table->string('address_number');
            $table->string('block')->nullable();
            $table->string('staircase')->nullable();
            $table->integer('floor')->nullable();
            $table->string('apartement')->nullable();
            $table->string('county');
            $table->string('city');
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('admin_form_client');
    }
}
