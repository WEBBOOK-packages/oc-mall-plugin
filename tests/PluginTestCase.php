<?php

declare(strict_types=1);

namespace WebBook\Mall\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use WebBook\Mall\Classes\Index\Index;
use WebBook\Mall\Classes\Index\Noop;
use WebBook\Mall\Models\Currency;
use WebBook\Mall\Updates\Seeders\MallSeeder;
use WebBook\Mall\Updates\Seeders\Tables\CustomerGroupTableSeeder;
use WebBook\Mall\Updates\Seeders\Tables\CustomerTableSeeder;
use WebBook\Mall\Updates\Seeders\Tables\CustomFieldTableSeeder;
use WebBook\Mall\Updates\Seeders\Tables\ProductTableSeeder;
use System;
use System\Classes\PluginManager;

class PluginTestCase extends \PluginTestCase
{
    use RefreshDatabase;
    use WithFaker;

    /**
     * Setup the test environment.
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        // Seed demo data
        if (version_compare(System::VERSION, '3.0', '<')) {
            $manager = PluginManager::instance();
            $manager->loadPlugins();
            $plugin = $manager->findByIdentifier('webbook.mall');
            $manager->registerPlugin($plugin);

            app()->call(MallSeeder::class);
            app()->call(CustomerGroupTableSeeder::class);
            app()->call(CustomerTableSeeder::class);
            app()->call(CustomFieldTableSeeder::class);
            app()->call(ProductTableSeeder::class);
        } else {
            $this->artisan('plugin:seed', [
                'namespace' => 'WebBook.Mall',
                'class'     => 'WebBook\Mall\Updates\Seeders\MallSeeder',
            ]);

            //@todo temporary solution to fix testing
            $this->artisan('plugin:seed', [
                'namespace' => 'WebBook.Mall',
                'class'     => 'WebBook\Mall\Updates\Seeders\Tables\CustomerGroupTableSeeder',
            ]);

            //@todo temporary solution to fix testing
            $this->artisan('plugin:seed', [
                'namespace' => 'WebBook.Mall',
                'class'     => 'WebBook\Mall\Updates\Seeders\Tables\CustomerTableSeeder',
            ]);

            //@todo temporary solution to fix testing
            $this->artisan('plugin:seed', [
                'namespace' => 'WebBook.Mall',
                'class'     => 'WebBook\Mall\Updates\Seeders\Tables\CustomFieldTableSeeder',
            ]);

            //@todo temporary solution to fix testing
            $this->artisan('plugin:seed', [
                'namespace' => 'WebBook.Mall',
                'class'     => 'WebBook\Mall\Updates\Seeders\Tables\ProductTableSeeder',
            ]);
        }

        // Set CHF as default currency
        Currency::setActiveCurrency(Currency::where('code', 'CHF')->first());

        // Bind No-Op Index
        app()->bind(Index::class, fn () => new Noop());
    }

    /**
     * Tear down the test environment.
     * @return void
     */
    public function tearDown(): void
    {
        parent::tearDown();
    }
}
