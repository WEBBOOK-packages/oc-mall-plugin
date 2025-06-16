<?php

namespace WebBook\Mall\Updates;

use October\Rain\Database\Updates\Migration;
use Schema;

class CreateWebBookMallCountryTax extends Migration
{
    public function up()
    {
        Schema::create('webbook_mall_country_tax', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->integer('country_id');
            $table->integer('tax_id');

            $table->unique(['country_id', 'tax_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('webbook_mall_country_tax');
    }
}
