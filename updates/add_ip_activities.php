<?php namespace LukeTowers\EasyAudit\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class AddIpActivities extends Migration
{
    public function up()
    {
        Schema::table('luketowers_easyaudit_activities', function($table)
        {
            $table->string('ip_address')->nullable()->index();
        });
    }

    public function down()
    {
        Schema::table('luketowers_easyaudit_activities', function($table)
        {
            $table->dropColumn('ip_address');
        });
    }
}