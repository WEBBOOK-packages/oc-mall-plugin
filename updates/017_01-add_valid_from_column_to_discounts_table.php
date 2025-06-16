<?php

namespace WebBook\Mall\Updates;

use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;
use Schema;

class AddValidFromColumnToDiscountsTable extends Migration
{
    public function up()
    {
        Schema::table('webbook_mall_discounts', function (Blueprint $table) {
            $table->dateTime('valid_from')->after('max_number_of_usages')->nullable();
        });
    }

    public function down()
    {
        Schema::table('webbook_mall_discounts', function (Blueprint $table) {
            $table->dropColumn(['valid_from']);
        });
    }
}
