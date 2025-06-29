<?php

namespace WebBook\Mall\Updates;

use October\Rain\Database\Updates\Migration;
use Schema;

class CreateWebBookMallPriceCategories extends Migration
{
    public function up()
    {
        Schema::create('webbook_mall_price_categories', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('name');
            $table->string('code');
            $table->integer('sort_order')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('webbook_mall_price_categories');
    }
}
