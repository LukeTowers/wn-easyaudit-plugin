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
            $table->string('event')->index();
            $table->string('description')->nullable();
            $table->integer('subject_id')->nullable();
            $table->string('subject_type')->nullable();
            $table->integer('source_id')->nullable();
            $table->string('source_type')->nullable();
            $table->json('properties')->nullable();
            $table->timestamp('created_at')->nullable();
            
            $table->index(['subject_id', 'subject_type']);
            $table->index(['source_id', 'source_type']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('luketowers_activitylog_activities');
    }

}