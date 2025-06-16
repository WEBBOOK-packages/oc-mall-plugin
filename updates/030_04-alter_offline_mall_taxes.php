<?php

declare(strict_types=1);

namespace WebBook\Mall\Updates;

use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;
use Schema;

class AlterWebBookMallTaxes_030_04 extends Migration
{
    /**
     * Install Migration
     *
     * @return void
     */
    public function up()
    {
        Schema::table('webbook_mall_taxes', function (Blueprint $table) {
            $table->boolean('is_enabled')->default(true)->after('is_default');
        });
    }

    /**
     * Uninstall Migration
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('webbook_mall_taxes', 'is_enabled')) {
            if (method_exists(Schema::class, 'dropColumns')) {
                Schema::dropColumns('webbook_mall_taxes', 'is_enabled');
            } else {
                Schema::table('webbook_mall_taxes', function (Blueprint $table) {
                    $table->dropColumn('is_enabled');
                });
            }
        }
    }
};
