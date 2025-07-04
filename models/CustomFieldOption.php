<?php

declare(strict_types=1);

namespace WebBook\Mall\Models;

use Model;
use October\Rain\Database\Traits\Sortable;
use October\Rain\Database\Traits\Validation;
use WebBook\Mall\Classes\Traits\HashIds;
use WebBook\Mall\Classes\Traits\PriceAccessors;
use System\Models\File;

class CustomFieldOption extends Model
{
    use Validation;
    use Sortable;
    use HashIds;
    use PriceAccessors;

    public const MORPH_KEY = 'mall.custom_field_option';

    public $implement = ['@RainLab.Translate.Behaviors.TranslatableModel'];

    public $translatable = ['name'];

    public $with = ['prices'];

    public $fillable = [
        'id',
        'name',
        'sort_order',
        'option_value',
        'custom_field_id',
    ];

    public $rules = [
        'name' => 'required',
    ];

    public $attachOne = [
        'image' => File::class,
    ];

    public $belongsTo = [
        'product'      => Product::class,
        'custom_field' => CustomField::class,
    ];

    public $morphMany = [
        'prices' => [Price::class, 'name' => 'priceable', 'conditions' => 'price_category_id is null'],
    ];

    /**
     * The parent's field type is store to make trigger conditions
     * work in the custom backend relationship form.
     *
     * @var string
     */
    public $field_type = '';

    public $table = 'webbook_mall_custom_field_options';

    public function afterDelete()
    {
        $this->prices()->delete();
    }
}
