<?php

namespace WebBook\Mall\Updates;

use October\Rain\Database\Updates\Migration;
use Schema;

class CreateWebBookMallProductCustomFieldOptions extends Migration
{
    public function up()
    {
        Schema::create('webbook_mall_custom_field_options', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->integer('custom_field_id')->unsigned()->nullable();
            $table->string('name');
            $table->string('option_value')->nullable();
            $table->integer('sort_order')->unsigned()->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('webbook_mall_custom_field_options');
    }
}
