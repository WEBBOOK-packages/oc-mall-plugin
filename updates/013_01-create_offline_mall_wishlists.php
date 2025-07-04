<?php

namespace WebBook\Mall\Updates;

use October\Rain\Database\Updates\Migration;
use Schema;

class CreateWebBookMallWishlists extends Migration
{
    public function up()
    {
        Schema::create('webbook_mall_wishlists', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('name');

            $table->string('session_id')->nullable()->index();
            $table->integer('customer_id')->nullable()->index();

            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('webbook_mall_wishlists');
    }
}
