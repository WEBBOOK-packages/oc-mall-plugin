<?php

namespace WebBook\Mall\Updates;

use October\Rain\Database\Updates\Migration;
use Schema;

class CreateWebBookMallShippingMethodRates extends Migration
{
    public function up()
    {
        Schema::create('webbook_mall_shipping_method_rates', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->integer('shipping_method_id')->unsigned();
            $table->integer('from_weight')->unsigned()->default(0);
            $table->integer('to_weight')->unsigned()->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('webbook_mall_shipping_method_rates');
    }
}
