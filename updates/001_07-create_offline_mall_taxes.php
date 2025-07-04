<?php

namespace WebBook\Mall\Updates;

use October\Rain\Database\Updates\Migration;
use Schema;

class CreateWebBookMallTaxes extends Migration
{
    public function up()
    {
        Schema::create('webbook_mall_taxes', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('name', 255);
            $table->decimal('percentage');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('webbook_mall_taxes');
    }
}
