<?php

namespace WebBook\Mall\Updates;

use October\Rain\Database\Updates\Migration;
use Schema;

class CreateWebBookMallProductCustomField extends Migration
{
    public function up()
    {
        Schema::create('webbook_mall_product_custom_field', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->integer('product_id')->unsigned();
            $table->integer('custom_field_id')->unsigned();
        });
    }

    public function down()
    {
        Schema::dropIfExists('webbook_mall_product_custom_field');
    }
}
