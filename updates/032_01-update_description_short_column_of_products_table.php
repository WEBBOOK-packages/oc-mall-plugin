<?php

namespace WebBook\Mall\Updates;

use October\Rain\Database\Updates\Migration;
use Schema;

class UpdateDescriptionShortColumnOfProductsToSupportNullableValuesTable extends Migration
{
    public function up(): void
    {
        Schema::table('webbook_mall_products', function ($table) {
            $table->text('description_short')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('webbook_mall_products', function ($table) {
            $table->text('description_short')->nullable(false)->change();
        });
    }
}
