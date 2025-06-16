<?php

namespace WebBook\Mall\Updates;

use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;
use Schema;

class AddNameColumnToIndexTable extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('webbook_mall_index')) {
            return;
        }
        Schema::table('webbook_mall_index', function (Blueprint $table) {
            if (! Schema::hasColumn('webbook_mall_index', 'name')) {
                $table->string('name', 191);
            }
        });
    }

    public function down()
    {
        // Do nothing.
    }
}
