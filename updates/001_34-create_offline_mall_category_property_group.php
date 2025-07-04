<?php

namespace WebBook\Mall\Updates;

use October\Rain\Database\Updates\Migration;
use Schema;

class CreateWebBookMallCategoryPropertyGroup extends Migration
{
    public function up()
    {
        Schema::create('webbook_mall_category_property_group', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->integer('category_id')->unsigned();
            $table->integer('property_group_id')->unsigned();
            $table->integer('relation_sort_order')->unsigned()->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            if (! app()->runningUnitTests()) {
                $table->index(['category_id', 'property_group_id'], 'idx_property_group_pivot');
            }
        });
    }

    public function down()
    {
        Schema::dropIfExists('webbook_mall_category_property_group');
    }
}
