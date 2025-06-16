<?php

declare(strict_types=1);

namespace WebBook\Mall;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\View;
use October\Rain\Database\Relations\Relation;
use WebBook\Mall\Classes\Registration\BootComponents;
use WebBook\Mall\Classes\Registration\BootEvents;
use WebBook\Mall\Classes\Registration\BootExtensions;
use WebBook\Mall\Classes\Registration\BootMails;
use WebBook\Mall\Classes\Registration\BootServiceContainer;
use WebBook\Mall\Classes\Registration\BootSettings;
use WebBook\Mall\Classes\Registration\BootTwig;
use WebBook\Mall\Classes\Registration\BootValidation;
use WebBook\Mall\Console\CheckCommand;
use WebBook\Mall\Console\IndexCommand;
use WebBook\Mall\Console\PurgeCommand;
use WebBook\Mall\Console\SeedDataCommand;
use WebBook\Mall\Models\CustomField;
use WebBook\Mall\Models\CustomFieldOption;
use WebBook\Mall\Models\Discount;
use WebBook\Mall\Models\ImageSet;
use WebBook\Mall\Models\PaymentMethod;
use WebBook\Mall\Models\Product;
use WebBook\Mall\Models\ServiceOption;
use WebBook\Mall\Models\ShippingMethod;
use WebBook\Mall\Models\ShippingMethodRate;
use WebBook\Mall\Models\Variant;
use System;
use System\Classes\PluginBase;

class Plugin extends PluginBase
{
    use BootEvents;
    use BootExtensions;
    use BootServiceContainer;
    use BootSettings;
    use BootComponents;
    use BootMails;
    use BootValidation;
    use BootTwig;

    /**
     * Required plugin dependencies.
     * @var array
     */
    public $require = [
        'RainLab.User',
        'RainLab.Location',
        'RainLab.Translate',
    ];

    /**
     * Required model morph-map relations, must be registered n the constructor
     * to make them available when the plugin migrations are run.
     * @var array
     */
    protected $relations = [
        Variant::MORPH_KEY            => Variant::class,
        Product::MORPH_KEY            => Product::class,
        ImageSet::MORPH_KEY           => ImageSet::class,
        Discount::MORPH_KEY           => Discount::class,
        CustomField::MORPH_KEY        => CustomField::class,
        PaymentMethod::MORPH_KEY      => PaymentMethod::class,
        ShippingMethod::MORPH_KEY     => ShippingMethod::class,
        CustomFieldOption::MORPH_KEY  => CustomFieldOption::class,
        ShippingMethodRate::MORPH_KEY => ShippingMethodRate::class,
        ServiceOption::MORPH_KEY      => ServiceOption::class,
    ];

    /**
     * Create a new plugin instance.
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        parent::__construct($app);
        Relation::morphMap($this->relations);
    }

    /**
     * Register this plugin.
     * @return void
     */
    public function register()
    {
        $this->registerServices();
        $this->registerTwigEnvironment();
    }

    /**
     * Boot this plugin.
     * @return void
     */
    public function boot()
    {
        $this->registerExtensions();
        $this->registerEvents();
        $this->registerValidationRules();

        $this->registerConsoleCommand('webbook.mall.check', CheckCommand::class);
        $this->registerConsoleCommand('webbook.mall.index', IndexCommand::class);
        $this->registerConsoleCommand('webbook.mall.purge', PurgeCommand::class);
        $this->registerConsoleCommand('webbook.mall.seed', SeedDataCommand::class);

        View::share('app_url', config('app.url'));
    }

    /**
     * Register Backend-Navigation items for this plugin.
     * @return array
     */
    public function registerNavigation()
    {
        $navigation = parent::registerNavigation();

        // Icon name has been changed from 'icon-star-half-full' to 'icon-star-half'
        if (version_compare(System::VERSION, '3.6', '>=')) {
            $navigation['mall-catalogue']['sideMenu']['mall-reviews']['icon'] = 'icon-star-half';
        }

        return $navigation;
    }
}
