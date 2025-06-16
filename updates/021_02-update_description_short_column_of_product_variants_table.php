<?php

namespace WebBook\Mall\Updates;

use October\Rain\Database\Updates\Migration;
use Schema;

class UpdateDescriptionShortColumnOfProductVariantsTable extends Migration
{
    public function up()
    {
        Schema::table('webbook_mall_product_variants', function ($table) {
            $table->text('description_short')->change();
        });
    }

    public function down()
    {
        Schema::table('webbook_mall_product_variants', function ($table) {
            $table->string('description_short', 255)->change();
        });
    }
}
