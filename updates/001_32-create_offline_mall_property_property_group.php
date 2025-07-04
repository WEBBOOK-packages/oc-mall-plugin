<?php

namespace WebBook\Mall\Updates;

use October\Rain\Database\Updates\Migration;
use Schema;

class CreateWebBookMallPropertyPropertyGroup extends Migration
{
    public function up()
    {
        Schema::create('webbook_mall_property_property_group', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->integer('property_id')->unsigned();
            $table->integer('property_group_id')->unsigned();
            $table->boolean('use_for_variants')->default(false);
            $table->string('filter_type')->nullable();
            $table->integer('sort_order')->unsigned()->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('webbook_mall_property_property_group');
    }
}
