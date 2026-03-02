<?php

declare(strict_types=1);

namespace WebBook\Mall\Updates;

use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;
use Schema;

class AddContactAndTaxFieldsToWebBookMallAddresses extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('webbook_mall_addresses')) {
            return;
        }

        Schema::table('webbook_mall_addresses', function (Blueprint $table) {
            if (! Schema::hasColumn('webbook_mall_addresses', 'phone')) {
                $table->string('phone', 50)->nullable();
            }

            if (! Schema::hasColumn('webbook_mall_addresses', 'tin')) {
                $table->string('tin', 32)->nullable();
            }

            if (! Schema::hasColumn('webbook_mall_addresses', 'vat')) {
                $table->string('vat', 32)->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('webbook_mall_addresses')) {
            return;
        }

        Schema::table('webbook_mall_addresses', function (Blueprint $table) {
            foreach (['phone', 'tin', 'vat'] as $column) {
                if (Schema::hasColumn('webbook_mall_addresses', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
}
