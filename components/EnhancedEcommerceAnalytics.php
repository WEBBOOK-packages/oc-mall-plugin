<?php

declare(strict_types=1);

namespace WebBook\Mall\Components;

use Cms\Classes\ComponentBase;

class EnhancedEcommerceAnalytics extends ComponentBase
{
    public $products;

    public function componentDetails()
    {
        return [
            'name'        => 'webbook.mall::lang.components.enhancedEcommerceAnalytics.details.name',
            'description' => 'webbook.mall::lang.components.enhancedEcommerceAnalytics.details.description',
        ];
    }

    public function defineProperties()
    {
        return [];
    }

    public function init()
    {
    }
}
