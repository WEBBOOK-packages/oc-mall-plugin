<?php

namespace WebBook\Mall\Updates;

use October\Rain\Database\Updates\Migration;
use WebBook\Mall\Models\GeneralSettings;

class SetUseStateDefaultSetting extends Migration
{
    public function up()
    {
        // To remain backwards compatible this setting is set to true.
        GeneralSettings::set('use_state', true);
    }

    public function down()
    {
        // Do nothing.
    }
}
