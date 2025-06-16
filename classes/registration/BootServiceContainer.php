<?php

namespace WebBook\Mall\Classes\Registration;

use Barryvdh\DomPDF\Facade;
use Barryvdh\DomPDF\PDF;
use DB;
use Dompdf\Dompdf;
use Hashids\Hashids;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\Facades\Cache;
use WebBook\Mall\Classes\Customer\DefaultSignInHandler;
use WebBook\Mall\Classes\Customer\DefaultSignUpHandler;
use WebBook\Mall\Classes\Customer\SignInHandler;
use WebBook\Mall\Classes\Customer\SignUpHandler;
use WebBook\Mall\Classes\Index\Filebase;
use WebBook\Mall\Classes\Index\Index;
use WebBook\Mall\Classes\Index\IndexNotSupportedException;
use WebBook\Mall\Classes\Index\MySQL\MySQL;
use WebBook\Mall\Classes\Payments\DefaultPaymentGateway;
use WebBook\Mall\Classes\Payments\WebBook;
use WebBook\Mall\Classes\Payments\PaymentGateway;
use WebBook\Mall\Classes\Payments\PayPalRest;
use WebBook\Mall\Classes\Payments\PostFinance;
use WebBook\Mall\Classes\Payments\Stripe;
use WebBook\Mall\Classes\User\UserProvider;
use WebBook\Mall\Classes\Utils\DefaultMoney;
use WebBook\Mall\Classes\Utils\Money;
use WebBook\Mall\Models\GeneralSettings;
use PDO;

trait BootServiceContainer
{
    protected function registerServices()
    {
        $this->app->bind(SignInHandler::class, fn () => new DefaultSignInHandler());
        $this->app->bind(SignUpHandler::class, fn () => new DefaultSignUpHandler());
        $this->app->singleton(Money::class, fn () => new DefaultMoney());
        $this->app->singleton(PaymentGateway::class, function () {
            $gateway = new DefaultPaymentGateway();
            $gateway->registerProvider(new WebBook());
            $gateway->registerProvider(new PayPalRest());
            $gateway->registerProvider(new Stripe());
            $gateway->registerProvider(new PostFinance());

            return $gateway;
        });
        $this->app->singleton(Hashids::class, fn () => new Hashids(config('app.key', 'oc-mall'), 8));
        $this->app->bind(Index::class, function () {
            $driver = Cache::rememberForever('webbook_mall.mysql.index.driver', function () {
                $driver = GeneralSettings::get('index_driver');

                if ($driver === null) {
                    GeneralSettings::set('index_driver', 'database');
                }

                return $driver;
            });

            try {
                if ($driver === 'filesystem') {
                    return new Filebase();
                } else {
                    $pdo = DB::connection()->getPdo();

                    if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) == 'sqlite') {
                        $pdo->sqliteCreateFunction('JSON_CONTAINS', function ($json, $val, $path = null) {
                            $array = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
                            $val = trim($val, '"');

                            if (!empty($path) && str_starts_with($path, '$.')) {
                                $path = trim(ltrim($path, '$.'), '"');
                                $array = $array[$path] ?? [];
                            }

                            if (strpos($val, '[') == 0 && strrpos($val, ']') == strlen($val)-1) {
                                $val = json_decode($val, true)[0];
                            }

                            return in_array($val, $array, true);
                        });
                    }

                    return new MySQL();
                }
            } catch (IndexNotSupportedException $e) {
                logger()->error(
                    '[WebBook.Mall] Your database does not support JSON data. Your index driver has been switched to "Filesystem". Update your database to make use of database indexing.'
                );
                GeneralSettings::set('index_driver', 'filesystem');
                Cache::forget('webbook_mall.mysql.index.driver');

                return new Filebase();
            }
        });

        $this->registerDomPDF();

        $this->registerUserProvider();
    }

    /**
     * Register barryvdh/laravel-dompdf
     */
    protected function registerDomPDF()
    {
        AliasLoader::getInstance()->alias('PDF', Facade::class);

        $this->app->bind('dompdf.options', function () {
            if ($defines = $this->app['config']->get('webbook.mall::pdf.defines')) {
                $options = [];

                foreach ($defines as $key => $value) {
                    $key           = strtolower(str_replace('DOMPDF_', '', $key));
                    $options[$key] = $value;
                }
            } else {
                $options = $this->app['config']->get('webbook.mall::pdf.options', []);
            }

            return $options;
        });

        $this->app->bind('dompdf', function () {
            $options = $this->app->make('dompdf.options');
            $dompdf  = new Dompdf($options);
            $dompdf->setBasePath(realpath(base_path('public')));

            return $dompdf;
        });
        $this->app->alias('dompdf', Dompdf::class);
        $this->app->bind('dompdf.wrapper', fn ($app) => new PDF($app['dompdf'], $app['config'], $app['files'], $app['view']));
    }

    protected function registerUserProvider()
    {
        // RainLab.User 3.0
        if (class_exists(\RainLab\User\Models\Setting::class)) {
            // RainLab.User excludes guests from logging in starting with 3.0.
            // We handle these restrictions ourselves, so we can allow guests to log in.
            $this->app->auth->provider('user', function ($app, array $config) {
                return new UserProvider($app['hash'], $config['model']);
            });
        }
    }
}
