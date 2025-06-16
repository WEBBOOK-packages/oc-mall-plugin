<?php

namespace WebBook\Mall\Updates;

use October\Rain\Database\Updates\Migration;
use Schema;

class AddIsDefaultColumnToTaxesTable extends Migration
{
    public function up()
    {
        Schema::table('webbook_mall_taxes', function ($table) {
            $table->boolean('is_default')->default(0);
        });
    }

    public function down()
    {
        Schema::table('webbook_mall_taxes', function ($table) {
            $table->dropColumn('is_default');
        });
    }
}
