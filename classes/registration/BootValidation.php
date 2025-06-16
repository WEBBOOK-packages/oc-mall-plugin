<?php

namespace WebBook\Mall\Classes\Registration;

use WebBook\Mall\Classes\Validation\NonExistingUserRule;

trait BootValidation
{
    protected function registerValidationRules()
    {
        $this->registerValidationRule('non_existing_user', NonExistingUserRule::class);
    }
}
