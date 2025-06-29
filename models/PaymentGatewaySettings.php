<?php

declare(strict_types=1);

namespace WebBook\Mall\Models;

use Illuminate\Support\Collection;
use Model;
use October\Rain\Database\Traits\Encryptable;
use WebBook\Mall\Classes\Payments\PaymentGateway;

class PaymentGatewaySettings extends Model
{
    use Encryptable;

    public $implement = ['System.Behaviors.SettingsModel'];

    public $settingsCode = 'webbook_mall_settings';

    public $settingsFields = '$/webbook/mall/models/settings/fields_payment_gateways.yaml';

    protected $encryptable = [];

    /**
     * @var PaymentGateway
     */
    protected $gateway;

    /**
     * @var Collection<PaymentProvider>
     */
    protected $providers;

    public function __construct(array $attributes = [])
    {
        $this->gateway   = app(PaymentGateway::class);
        $this->providers = collect($this->gateway->getProviders());
        $this->providers->each(function ($provider) {
            $this->encryptable = array_merge($this->encryptable, $provider->encryptedSettings());
        });

        parent::__construct($attributes);
    }

    /**
     * Extend the setting form with input fields for each
     * registered plugin.
     */
    public function getFieldConfig()
    {
        if ($this->fieldConfig !== null) {
            return $this->fieldConfig;
        }

        $config                 = parent::getFieldConfig();
        $config->tabs['fields'] = [];

        $this->providers->each(function ($provider) use ($config) {
            $settings = $this->setDefaultTab($provider->settings(), $provider->name());

            $config->tabs['fields'] = array_merge($config->tabs['fields'], $settings);
        });

        return $config;
    }

    protected function setDefaultTab(array $settings, $tab)
    {
        return array_map(function ($i) use ($tab) {
            if (! isset($i['tab'])) {
                $i['tab'] = $tab;
            }

            return $i;
        }, $settings);
    }
}
