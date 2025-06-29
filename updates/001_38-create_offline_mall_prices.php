<?php

namespace WebBook\Mall\Updates;

use October\Rain\Database\Updates\Migration;
use Schema;

class CreateWebBookMallPrices extends Migration
{
    public function up()
    {
        Schema::create('webbook_mall_prices', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->integer('currency_id')->unsigned();
            $table->integer('priceable_id')->unsigned();
            $table->string('priceable_type');
            $table->integer('price')->nullable();
            $table->integer('price_category_id')->unsigned()->nullable();
            $table->string('field')->nullable();
            $table->timestamps();

            if (! app()->runningUnitTests()) {
                $table->unique(
                    ['price_category_id', 'priceable_id', 'priceable_type', 'currency_id', 'field'],
                    'unique_price'
                );
                $table->index(['priceable_id', 'priceable_type'], 'idx_price_priceable');
                $table->index('currency_id', 'idx_price_currency');
            }
        });
    }

    public function down()
    {
        Schema::dropIfExists('webbook_mall_prices');
    }
}
