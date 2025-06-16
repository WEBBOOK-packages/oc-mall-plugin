<?php

declare(strict_types=1);

namespace WebBook\Mall\Updates\Seeders\Tables;

use October\Rain\Database\Updates\Seeder;
use WebBook\Mall\Models\PaymentMethod;

class PaymentMethodTableSeeder extends Seeder
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

        PaymentMethod::create([
            'name'              => trans('webbook.mall::demo.payment_methods.invoice'),
            'payment_provider'  => 'webbook',
            'sort_order'        => 1,
            'is_default'        => true,
        ]);

        PaymentMethod::create([
            'name'              => 'PayPal',
            'payment_provider'  => 'paypal-rest',
            'sort_order'        => 2,
        ]);

        PaymentMethod::create([
            'name'              => 'Stripe',
            'payment_provider'  => 'stripe',
            'sort_order'        => 3,
        ]);
    }
}
