<?php

declare(strict_types=1);

namespace WebBook\Mall\Models;

use October\Rain\Database\Pivot;
use October\Rain\Database\Traits\Nullable;

class PropertyGroupProperty extends Pivot
{
    use Nullable;

    public $nullable = ['filter_type'];

    public $casts = [
        'use_for_variants',
    ];

    public static function getFilterTypeOptions($dashes = true)
    {
        return [
            null    => ($dashes ? '-- ' : '') . trans('webbook.mall::lang.properties.filter_types.none'),
            'set'   => trans('webbook.mall::lang.properties.filter_types.set'),
            'range' => trans('webbook.mall::lang.properties.filter_types.range'),
        ];
    }
}
