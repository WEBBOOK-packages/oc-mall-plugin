<?php

namespace WebBook\Mall\Updates;

use October\Rain\Database\Updates\Migration;
use Schema;

class CreateWebBookMallCartDiscount extends Migration
{
    public function up()
    {
        Schema::create('webbook_mall_cart_discount', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->integer('cart_id')->unsigned();
            $table->integer('discount_id')->unsigned();

            if (! app()->runningUnitTests()) {
                $table->index(['cart_id', 'discount_id'], 'idx_cart_discount_pivot');
            }
        });
    }

    public function down()
    {
        Schema::dropIfExists('webbook_mall_cart_discount');
    }
}
