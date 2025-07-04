<?php

declare(strict_types=1);

namespace WebBook\Mall\Models;

use DB;
use Model;
use October\Rain\Database\Traits\Validation;
use WebBook\Mall\Classes\Traits\HashIds;
use WebBook\Mall\Classes\Traits\PriceAccessors;
use System\Models\File;

class CustomField extends Model
{
    use Validation;
    use HashIds;
    use PriceAccessors;

    public const MORPH_KEY = 'mall.custom_field';

    public $implement = ['@RainLab.Translate.Behaviors.TranslatableModel'];

    public $translatable = ['name'];

    public $with = ['custom_field_options', 'prices'];

    public $casts = [
        'required' => 'boolean',
    ];

    public $fillable = [
        'name',
        'type',
        'required',
    ];

    public $rules = [
        'product_id' => 'exists:webbook_mall_products,id',
        'name'       => 'required',
        'type'       => 'in:text,textarea,dropdown,checkbox,color,image',
        'required'   => 'boolean',
    ];

    public $hasMany = [
        'custom_field_options' => [CustomFieldOption::class, 'order' => 'sort_order'],
    ];

    public $morphMany = [
        'prices' => [Price::class, 'name' => 'priceable', 'conditions' => 'price_category_id is null'],
    ];

    public $belongsToMany = [
        'products' => [
            Product::class,
            'table'    => 'webbook_mall_product_custom_field',
            'key'      => 'custom_field_id',
            'otherKey' => 'product_id',
        ],
    ];

    public $attachOne = [
        'image' => File::class,
    ];

    public $table = 'webbook_mall_custom_fields';

    public function afterDelete()
    {
        $this->prices()->delete();
        $this->custom_field_options()->delete();
        DB::table('webbook_mall_product_custom_field')->where('custom_field_id', $this->id)->delete();
    }

    public function getTypeLabelAttribute()
    {
        return trans('webbook.mall::lang.custom_field_options.' . $this->type);
    }

    public function getTypeDropdownAttribute()
    {
        return $this->type;
    }
}
