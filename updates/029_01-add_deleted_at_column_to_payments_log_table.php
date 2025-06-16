<?php

namespace WebBook\Mall\Updates;

use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;
use Schema;

class AddDeletedAtColumnToPaymentsLogTable extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('webbook_mall_payments_log')) {
            return;
        }
        Schema::table('webbook_mall_payments_log', function (Blueprint $table) {
            if (! Schema::hasColumn('webbook_mall_payments_log', 'deleted_at')) {
                $table->timestamp('deleted_at')->nullable();
            }
        });
    }

    public function down()
    {
        if (! Schema::hasTable('webbook_mall_payments_log')) {
            return;
        }
        Schema::table('webbook_mall_payments_log', function (Blueprint $table) {
            if (Schema::hasColumn('webbook_mall_payments_log', 'deleted_at')) {
                $table->dropColumn(['deleted_at']);
            }
        });
    }
}
