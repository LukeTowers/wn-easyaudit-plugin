<?php namespace LukeTowers\EasyAudit\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

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