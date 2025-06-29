<?php

namespace WebBook\Mall\Updates;

use October\Rain\Database\Updates\Migration;
use Schema;

class CreateWebBookMallCartCustomFieldValue extends Migration
{
    public function up()
    {
        Schema::create('webbook_mall_cart_custom_field_value', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->integer('cart_product_id')->unsigned()->nullable();
            $table->integer('custom_field_id')->unsigned();
            $table->integer('custom_field_option_id')->nullable()->unsigned();
            $table->text('value')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('webbook_mall_cart_custom_field_value');
    }
}
