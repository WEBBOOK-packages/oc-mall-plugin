<?php

namespace WebBook\Mall\Updates;

use October\Rain\Database\Updates\Migration;
use Schema;

class AddPDFPartialToPaymentMethod extends Migration
{
    public function up()
    {
        Schema::table('webbook_mall_payment_methods', function ($table) {
            $table->string('pdf_partial')->after('fee_percentage')->nullable();
        });
    }

    public function down()
    {
        Schema::table('webbook_mall_payment_methods', function ($table) {
            $table->dropColumn(['pdf_partial']);
        });
    }
}
