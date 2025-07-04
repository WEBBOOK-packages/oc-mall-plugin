<?php

declare(strict_types=1);

namespace WebBook\Mall\Models;

use Model;
use October\Rain\Database\Traits\Validation;

class CategoryReviewTotal extends Model
{
    use Validation;

    public $table = 'webbook_mall_category_review_totals';

    public $timestamps = false;

    public $rules = [
        'rating'             => 'required',
        'review_category_id' => 'nullable|exists:webbook_mall_review_categories,id',
    ];

    public $fillable = [
        'review_category_id',
        'rating',
        'product_id',
        'variant_id',
    ];

    public $belongsTo = [
        'review_category' => ReviewCategory::class,
        'product'         => Product::class,
        'variant'         => Variant::class,
    ];

    public $casts = [
        'rating' => 'float',
    ];
}
