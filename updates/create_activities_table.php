<?php

namespace LukeTowers\EasyAudit\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class CreateActivitiesTable extends Migration
{
    public function up()
    {
        Schema::create('luketowers_easyaudit_activities', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('log')->default('default')->index();
            $table->string('event')->index();
            $table->string('description')->nullable();
            $table->integer('subject_id')->unsigned()->nullable();
            $table->string('subject_type')->nullable();
            $table->integer('source_id')->unsigned()->nullable();
            $table->string('source_type')->nullable();
            $table->mediumText('properties')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['subject_id', 'subject_type']);
            $table->index(['source_id', 'source_type']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('luketowers_easyaudit_activities');
    }
}
