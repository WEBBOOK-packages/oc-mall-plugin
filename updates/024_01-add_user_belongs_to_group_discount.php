<?php

namespace WebBook\Mall\Updates;

use October\Rain\Database\Updates\Migration;
use Schema;

class AddUserBelongsToGroupDiscount extends Migration
{
    public function up()
    {
        Schema::table('webbook_mall_discounts', function ($table) {
            $table->integer('customer_group_id')->unsigned()->nullable();
        });
    }

    public function down()
    {
        Schema::table('webbook_mall_discounts', function ($table) {
            $table->dropColumn(['customer_group_id']);
        });
    }
}
