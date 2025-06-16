<?php

namespace WebBook\Mall\Updates;

use October\Rain\Database\Updates\Migration;
use Schema;

class AddIdentifierColumnsForProductsAndVariants extends Migration
{
    public function up()
    {
        Schema::table('webbook_mall_products', function ($table) {
            $table->string('mpn')->nullable();
            $table->string('gtin')->nullable();
        });
        Schema::table('webbook_mall_product_variants', function ($table) {
            $table->string('mpn')->nullable();
            $table->string('gtin')->nullable();
        });
    }

    public function down()
    {
        Schema::table('webbook_mall_products', function ($table) {
            $table->dropColumn(['mpn', 'gtin']);
        });
        Schema::table('webbook_mall_product_variants', function ($table) {
            $table->dropColumn(['mpn', 'gtin']);
        });
    }
}
