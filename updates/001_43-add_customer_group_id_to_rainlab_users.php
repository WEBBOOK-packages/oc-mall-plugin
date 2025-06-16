<?php

namespace WebBook\Mall\Updates;

use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;
use Schema;

class AddCustomerGroupIdToRainlabUsers extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->integer('webbook_mall_customer_group_id')->nullable();
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'webbook_mall_customer_group_id')) {
                $table->dropColumn(['webbook_mall_customer_group_id']);
            }
        });
    }
}
