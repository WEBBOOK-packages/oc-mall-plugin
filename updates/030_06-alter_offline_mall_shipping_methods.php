<?php

declare(strict_types=1);

namespace WebBook\Mall\Updates;

use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;
use Schema;

class AlterWebBookMallShippingMethods_030_06 extends Migration
{
    /**
     * Install Migration
     *
     * @return void
     */
    public function up()
    {
        Schema::table('webbook_mall_shipping_methods', function (Blueprint $table) {
            $table->boolean('is_enabled')->default(true)->after('sort_order');
            $table->boolean('is_default')->default(false)->after('sort_order');
        });
    }

    /**
     * Uninstall Migration
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('webbook_mall_shipping_methods', 'is_default')) {
            if (method_exists(Schema::class, 'dropColumns')) {
                Schema::dropColumns('webbook_mall_payment_methods', 'is_default');
            } else {
                Schema::table('webbook_mall_shipping_methods', function (Blueprint $table) {
                    $table->dropColumn('is_default');
                });
            }
        }

        if (Schema::hasColumn('webbook_mall_shipping_methods', 'is_enabled')) {
            if (method_exists(Schema::class, 'dropColumns')) {
                Schema::dropColumns('webbook_mall_shipping_methods', 'is_enabled');
            } else {
                Schema::table('webbook_mall_shipping_methods', function (Blueprint $table) {
                    $table->dropColumn('is_enabled');
                });
            }
        }
    }
};
