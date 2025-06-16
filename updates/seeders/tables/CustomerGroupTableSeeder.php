<?php

declare(strict_types=1);

namespace WebBook\Mall\Updates\Seeders\Tables;

use October\Rain\Database\Updates\Seeder;
use WebBook\Mall\Models\CustomerGroup;

class CustomerGroupTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * @param bool $useDemo
     * @return void
     */
    public function run(bool $useDemo = false)
    {
        if (!$useDemo && config('app.env') != 'testing') {
            return;
        }

        CustomerGroup::create([
            'name' => trans('webbook.mall::demo.customer_groups.silver.name'),
            'code' => 'silver',
        ]);

        CustomerGroup::create([
            'name' => trans('webbook.mall::demo.customer_groups.gold.name'),
            'code' => 'gold',
        ]);

        CustomerGroup::create([
            'name' => trans('webbook.mall::demo.customer_groups.diamond.name'),
            'code' => 'diamond',
        ]);
    }
}
