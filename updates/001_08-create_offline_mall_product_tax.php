<?php

namespace WebBook\Mall\Updates;

use Illuminate\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;
use Schema;

class CreateWebBookMallProductTax extends Migration
{
    public function up()
    {
        Schema::create('webbook_mall_product_tax', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->integer('tax_id');
            $table->integer('product_id');

            $table->unique(['tax_id', 'product_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('webbook_mall_product_tax');
    }
}
