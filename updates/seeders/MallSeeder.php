<?php

declare(strict_types=1);

namespace WebBook\Mall\Updates\Seeders;

use October\Rain\Database\Updates\Seeder;
use WebBook\Mall\Updates\Seeders\Tables\CategoryTableSeeder;
use WebBook\Mall\Updates\Seeders\Tables\CurrencyTableSeeder;
use WebBook\Mall\Updates\Seeders\Tables\NotificationTableSeeder;
use WebBook\Mall\Updates\Seeders\Tables\OrderStateTableSeeder;
use WebBook\Mall\Updates\Seeders\Tables\PaymentMethodTableSeeder;
use WebBook\Mall\Updates\Seeders\Tables\PriceCategoryTableSeeder;
use WebBook\Mall\Updates\Seeders\Tables\PropertyTableSeeder;
use WebBook\Mall\Updates\Seeders\Tables\ShippingMethodTableSeeder;
use WebBook\Mall\Updates\Seeders\Tables\TaxTableSeeder;

class MallSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * @return void
     */
    public function run()
    {
        $this->call([
            PriceCategoryTableSeeder::class,
            CurrencyTableSeeder::class,
            CategoryTableSeeder::class,
            TaxTableSeeder::class,
            PaymentMethodTableSeeder::class,
            ShippingMethodTableSeeder::class,
            PropertyTableSeeder::class,
            OrderStateTableSeeder::class,
            NotificationTableSeeder::class,
        ]);
    }
}
