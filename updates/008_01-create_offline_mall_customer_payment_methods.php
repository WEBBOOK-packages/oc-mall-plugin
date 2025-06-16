<?php

namespace WebBook\Mall\Updates;

use October\Rain\Database\Updates\Migration;
use Schema;

class CreateWebBookMallCustomerPaymentMethods extends Migration
{
    public function up()
    {
        Schema::create('webbook_mall_customer_payment_methods', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('name')->nullabe();
            $table->integer('customer_id');
            $table->integer('payment_method_id');
            $table->boolean('is_default')->default(0);
            $table->mediumText('data')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
        });
        Schema::table('webbook_mall_orders', function ($table) {
            $table->integer('customer_payment_method_id')->nullable();
        });
        Schema::table('webbook_mall_carts', function ($table) {
            $table->integer('customer_payment_method_id')->nullable();
        });
        Schema::table('webbook_mall_customers', function ($table) {
            $table->string('stripe_customer_id')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('webbook_mall_customer_payment_methods');
        Schema::table('webbook_mall_orders', function ($table) {
            $table->dropColumn(['customer_payment_method_id']);
        });
        Schema::table('webbook_mall_carts', function ($table) {
            $table->dropColumn(['customer_payment_method_id']);
        });
        Schema::table('webbook_mall_customers', function ($table) {
            $table->dropColumn(['stripe_customer_id']);
        });
    }
}
