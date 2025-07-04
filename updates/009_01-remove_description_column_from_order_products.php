<?php

namespace WebBook\Mall\Updates;

use October\Rain\Database\Updates\Migration;
use Schema;

class RemoveDescriptionColumnFromOrderProducts extends Migration
{
    public function up()
    {
        Schema::table('webbook_mall_order_products', function ($table) {
            if (Schema::hasColumn('webbook_mall_order_products', 'description')) {
                $table->dropColumn(['description']);
            }
        });
    }

    public function down()
    {
        Schema::table('webbook_mall_order_products', function ($table) {
            $table->longText('description')->nullable();
        });
    }
}
