<?php

declare(strict_types=1);

namespace WebBook\Mall\Updates\Seeders\Tables;

use October\Rain\Database\Updates\Seeder;
use WebBook\Mall\Models\ReviewCategory;

class ReviewCategoryTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * @param bool $useDemo
     * @return void
     */
    public function run(bool $useDemo = false)
    {
        if (!$useDemo) {
            return;
        }

        ReviewCategory::create([
            'name' => trans('webbook.mall::demo.review_categories.price'),
        ]);

        ReviewCategory::create([
            'name' => trans('webbook.mall::demo.review_categories.design'),
        ]);

        ReviewCategory::create([
            'name' => trans('webbook.mall::demo.review_categories.quality'),
        ]);
    }
}
