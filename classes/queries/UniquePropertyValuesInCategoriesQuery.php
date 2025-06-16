<?php

namespace WebBook\Mall\Classes\Queries;

use DB;
use Illuminate\Support\Collection;
use October\Rain\Database\QueryBuilder;

/**
 * This query is used to get a list of all unique property values in one or
 * more categories. It is used to display a set of possible filters
 * for all available property values.
 *
 * @deprecated 3.4.0 use UniquePropertyValue::hydratePropertyValuesForCategories($categories)
 * @see \WebBook\Mall\Models\UniquePropertyValue
 */
class UniquePropertyValuesInCategoriesQuery
{
    /**
     * An array of category ids.
     * @var Collection
     */
    protected $categories;

    public function __construct($categories)
    {
        $this->categories = $categories;
    }

    /**
     * Return a query to get all unique product property values.
     *
     * @return QueryBuilder
     */
    public function query()
    {
        return DB::table('webbook_mall_products')
            ->selectRaw(
                '
                MIN(webbook_mall_property_values.id) AS id,
                webbook_mall_property_values.value,
                webbook_mall_property_values.index_value,
                webbook_mall_property_values.property_id'
            )
            ->where(function ($q) {
                $q->where(function ($q) {
                    $q->where('webbook_mall_products.published', true)
                        ->whereNull('webbook_mall_product_variants.id');
                })->orWhere('webbook_mall_product_variants.published', true);
            })
            ->whereIn('webbook_mall_category_product.category_id', $this->categories->pluck('id'))
            ->whereNull('webbook_mall_product_variants.deleted_at')
            ->whereNull('webbook_mall_products.deleted_at')
            ->where('webbook_mall_property_values.value', '<>', '')
            ->whereNotNull('webbook_mall_property_values.value')
            ->groupBy(
                'webbook_mall_property_values.value',
                'webbook_mall_property_values.index_value',
                'webbook_mall_property_values.property_id'
            )
            ->leftJoin(
                'webbook_mall_product_variants',
                'webbook_mall_products.id',
                '=',
                'webbook_mall_product_variants.product_id'
            )
            ->leftJoin(
                'webbook_mall_category_product',
                'webbook_mall_products.id',
                '=',
                'webbook_mall_category_product.product_id'
            )
            ->join(
                'webbook_mall_property_values',
                'webbook_mall_products.id',
                '=',
                'webbook_mall_property_values.product_id'
            );
    }
}
