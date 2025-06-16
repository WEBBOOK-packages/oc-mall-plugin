<?php

declare(strict_types=1);

namespace WebBook\Mall\Updates\Seeders\Tables;

use October\Rain\Database\Updates\Seeder;
use WebBook\Mall\Models\Product;
use WebBook\Mall\Updates\Seeders\Demo\Cruiser1000;
use WebBook\Mall\Updates\Seeders\Demo\Cruiser1500;
use WebBook\Mall\Updates\Seeders\Demo\Cruiser3000;
use WebBook\Mall\Updates\Seeders\Demo\Cruiser3500;
use WebBook\Mall\Updates\Seeders\Demo\Cruiser5000;
use WebBook\Mall\Updates\Seeders\Demo\GiftCard100;
use WebBook\Mall\Updates\Seeders\Demo\GiftCard200;
use WebBook\Mall\Updates\Seeders\Demo\GiftCard50;
use WebBook\Mall\Updates\Seeders\Demo\Jersey;
use WebBook\Mall\Updates\Seeders\Demo\RedShirt;

class ProductTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * @param bool $useDemo
     * @return void
     */
    public function run(bool $useDemo = false)
    {
        if (!$useDemo) {
            if (config('app.env') == 'testing') {
                $product = Product::create([
                    'name' => 'Test',
                    'slug' => 'test',
                    'stock' => 20,
                    'published' => true,
                ]);
                $product->price = [
                    'CHF' => 20,
                    'EUR' => 30,
                ];

                $product = Product::create([
                    'name' => 'Test 2',
                    'slug' => 'test-2',
                    'stock' => 90,
                    'published' => true,
                ]);
                $product->price = [
                    'CHF' => 30,
                    'EUR' => 40,
                ];
            }

            return;
        }

        // Bikes
        (new Cruiser1000())->create();
        (new Cruiser1500())->create();
        (new Cruiser3000())->create();
        (new Cruiser3500())->create();
        (new Cruiser5000())->create();

        // Clothing
        (new RedShirt())->create();
        (new Jersey())->create();

        // Gift Cards
        (new GiftCard50())->create();
        (new GiftCard100())->create();
        (new GiftCard200())->create();
    }
}
