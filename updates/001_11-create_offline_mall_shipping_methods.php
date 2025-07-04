<?php

namespace WebBook\Mall\Updates;

use October\Rain\Database\Updates\Migration;
use Schema;

class CreateWebBookMallShippingMethods extends Migration
{
    public function up()
    {
        Schema::create('webbook_mall_shipping_methods', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->integer('sort_order')->unsigned()->nullable();
            $table->integer('guaranteed_delivery_days')->nullable();
            $table->boolean('price_includes_tax')->default(true);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('webbook_mall_shipping_methods');
    }
}
