<?php

namespace WebBook\Mall\Updates;

use October\Rain\Database\Updates\Migration;
use Schema;

class CreateWebBookMallCartProducts extends Migration
{
    public function up()
    {
        Schema::create('webbook_mall_cart_products', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->integer('cart_id')->unsigned()->nullable();
            $table->integer('product_id')->unsigned();
            $table->integer('variant_id')->unsigned()->nullable();
            $table->integer('quantity')->default(1);
            $table->integer('weight')->unisgned()->nullable();
            $table->text('price');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('webbook_mall_cart_products');
    }
}
