<?php

namespace WebBook\Mall\Updates;

use October\Rain\Database\Updates\Migration;
use Schema;

class CreateWebBookMallPaymentMethods extends Migration
{
    public function up()
    {
        Schema::create('webbook_mall_payment_methods', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('name');
            $table->string('code');
            $table->text('description')->nullable();
            $table->text('instructions')->nullable();
            $table->text('payment_provider');
            $table->integer('sort_order')->unsigned()->nullable();
            $table->string('fee_label')->nullable();
            $table->decimal('fee_percentage', 5, 2)->unsigned()->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('webbook_mall_payment_methods');
    }
}
