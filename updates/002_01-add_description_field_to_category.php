<?php

namespace WebBook\Mall\Updates;

use October\Rain\Database\Updates\Migration;
use Schema;

class AddDescriptionFieldToCategory extends Migration
{
    public function up()
    {
        Schema::table('webbook_mall_categories', function ($table) {
            $table->text('description')->nullable();
        });
    }

    public function down()
    {
        Schema::table('webbook_mall_categories', function ($table) {
            $table->dropColumn(['description']);
        });
    }
}
