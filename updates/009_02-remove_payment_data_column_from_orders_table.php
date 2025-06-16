<?php

namespace WebBook\Mall\Updates;

use October\Rain\Database\Updates\Migration;
use Schema;

class RemovePaymentDataColumnFromOrdersTable extends Migration
{
    public function up()
    {
        Schema::table('webbook_mall_orders', function ($table) {
            if (Schema::hasColumn('webbook_mall_orders', 'payment_data')) {
                $table->dropColumn(['payment_data']);
            }
        });
    }

    public function down()
    {
        Schema::table('webbook_mall_orders', function ($table) {
            $table->mediumText('payment_data')->nullable();
        });
    }
}
