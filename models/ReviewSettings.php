<?php

declare(strict_types=1);

namespace WebBook\Mall\Models;

use Model;

class ReviewSettings extends Model
{
    public $implement = ['System.Behaviors.SettingsModel'];

    public $settingsCode = 'webbook_mall_settings';

    public $settingsFields = '$/webbook/mall/models/settings/fields_reviews.yaml';

    public function initSettingsData()
    {
        $this->enabled         = true;
        $this->moderated       = false;
        $this->allow_anonymous = false;
    }
}
