<?php

namespace WebBook\Mall\Updates;

use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;
use Schema;

class AddShippingMethodIdToWishlistsTable extends Migration
{
    public function up()
    {
        Schema::table('webbook_mall_wishlists', function (Blueprint $table) {
            $table->integer('shipping_method_id')->nullable();
        });
    }

    public function down()
    {
        Schema::table('webbook_mall_wishlists', function (Blueprint $table) {
            $table->dropColumn(['shipping_method_id']);
        });
    }
}
