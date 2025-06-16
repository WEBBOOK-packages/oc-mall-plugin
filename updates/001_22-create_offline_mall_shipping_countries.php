<?php

namespace WebBook\Mall\Updates;

use October\Rain\Database\Updates\Migration;
use Schema;

class CreateWebBookMallShippingCountries extends Migration
{
    public function up()
    {
        Schema::create('webbook_mall_shipping_countries', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->integer('shipping_method_id')->unsigned();
            $table->integer('country_id')->unsigned();
        });
    }

    public function down()
    {
        Schema::dropIfExists('webbook_mall_shipping_countries');
    }
}
