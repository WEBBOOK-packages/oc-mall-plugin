<?php

declare(strict_types=1);

namespace WebBook\Mall\Updates;

use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;
use Schema;

class a extends Migration
{
    /**
     * Install Migration
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('webbook_mall_order_states', function (Blueprint $table) {
            $table->boolean('is_internal')->default(false)->after('is_enabled');
            $table->unsignedInteger('display_state_id')->nullable()->after('is_internal');
        });
    }

    /**
     * Uninstall Migration
     *
     * @return void
     */
    public function down(): void
    {
        if (
            Schema::hasColumn('webbook_mall_order_states', 'display_state_id') ||
            Schema::hasColumn('webbook_mall_order_states', 'is_internal')
        ) {
            if (method_exists(Schema::class, 'dropColumns')) {
                Schema::dropColumns('webbook_mall_order_states', 'display_state_id', 'is_internal');
            } else {
                Schema::table('webbook_mall_order_states', function (Blueprint $table) {
                    if (Schema::hasColumn('webbook_mall_order_states', 'display_state_id')) {
                        $table->dropColumn('display_state_id');
                    }
                    if (Schema::hasColumn('webbook_mall_order_states', 'is_internal')) {
                        $table->dropColumn('is_internal');
                    }
                });
            }
        }
    }
};
