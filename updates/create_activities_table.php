<?php namespace LukeTowers\ActivityLog\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreateActivitiesTable extends Migration
{

    public function up()
    {
        Schema::create('luketowers_activitylog_activities', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('log_name')->nullable()->index();
            $table->string('description');
            $table->integer('subject_id')->nullable();
            $table->string('subject_type')->nullable();
            $table->integer('causer_id')->nullable();
            $table->string('causer_type')->nullable();
            $table->json('properties')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['subject_id', 'subject_type']);
            $table->index(['causer_id', 'causer_type']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('luketowers_activitylog_activities');
    }

}