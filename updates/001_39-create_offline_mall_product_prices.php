<?php

namespace WebBook\Mall\Updates;

use October\Rain\Database\Updates\Migration;
use Schema;

class CreateWebBookMallProductPrices extends Migration
{
    public function up()
    {
        Schema::create('webbook_mall_product_prices', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->integer('price')->nullable();
            $table->integer('product_id')->unsigned();
            $table->integer('variant_id')->unsigned()->nullable();
            $table->integer('currency_id')->unsigned();
            $table->timestamps();

            if (! app()->runningUnitTests()) {
                $table->unique(['product_id', 'currency_id', 'variant_id'], 'product_price_unique_price');
                $table->index('product_id', 'idx_product_price_product');
                $table->index('variant_id', 'idx_product_price_variant');
                $table->index('currency_id', 'idx_product_price_currency');
            }
        });
    }

    public function down()
    {
        Schema::dropIfExists('webbook_mall_product_prices');
    }
}
