<?php

namespace WebBook\Mall\Updates;

use October\Rain\Database\Updates\Migration;
use Schema;

class CreateWebBookMallProductAccessory extends Migration
{
    public function up()
    {
        Schema::create('webbook_mall_product_accessory', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->integer('product_id')->unsigned();
            $table->integer('accessory_id')->unsigned();
        });
    }

    public function down()
    {
        Schema::dropIfExists('webbook_mall_product_accessory');
    }
}
