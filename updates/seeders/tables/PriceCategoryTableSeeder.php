<?php

declare(strict_types=1);

namespace WebBook\Mall\Updates\Seeders\Tables;

use October\Rain\Database\Updates\Seeder;
use WebBook\Mall\Models\PriceCategory;

class PriceCategoryTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * @param bool $useDemo
     * @return void
     */
    public function run(bool $useDemo = false)
    {
        if ($useDemo) {
            return;
        }

        $category = PriceCategory::create([
            'code'      => 'old_price',
            'name'      => trans('webbook.mall::demo.price_categories.old_price_name'),
            'title'     => trans('webbook.mall::demo.price_categories.old_price_label'),
        ]);
        $category->translateContext('de');

        $category = PriceCategory::create([
            'code'      => 'msrp',
            'name'      => trans('webbook.mall::demo.price_categories.msrp_price_name'),
            'title'     => trans('webbook.mall::demo.price_categories.msrp_price_label'),
        ]);
        $category->translateContext('de');
    }
}
