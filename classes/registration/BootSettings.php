<?php

namespace WebBook\Mall\Classes\Registration;

use Backend\Facades\Backend;
use WebBook\Mall\Models\FeedSettings;
use WebBook\Mall\Models\GeneralSettings;
use WebBook\Mall\Models\PaymentGatewaySettings;
use WebBook\Mall\Models\ReviewSettings;

trait BootSettings
{
    public function registerSettings()
    {
        return [
            'general_settings'          => [
                'label'       => 'webbook.mall::lang.general_settings.label',
                'description' => 'webbook.mall::lang.general_settings.description',
                'category'    => 'webbook.mall::lang.general_settings.category',
                'icon'        => 'icon-shopping-cart',
                'class'       => GeneralSettings::class,
                'order'       => 0,
                'permissions' => ['webbook.mall.settings.manage_general'],
                'keywords'    => 'shop store mall general',
                'size'        => 'huge',
            ],
            'currency_settings'         => [
                'label'       => 'webbook.mall::lang.currency_settings.label',
                'description' => 'webbook.mall::lang.currency_settings.description',
                'category'    => 'webbook.mall::lang.general_settings.category',
                'icon'        => 'icon-money',
                'url'         => Backend::url('webbook/mall/currencies'),
                'order'       => 20,
                'permissions' => ['webbook.mall.settings.manage_currency'],
                'keywords'    => 'shop store mall currency',
            ],
            'price_categories_settings' => [
                'label'       => 'webbook.mall::lang.price_category_settings.label',
                'description' => 'webbook.mall::lang.price_category_settings.description',
                'category'    => 'webbook.mall::lang.general_settings.category',
                'icon'        => 'icon-pie-chart',
                'url'         => Backend::url('webbook/mall/pricecategories'),
                'order'       => 20,
                'permissions' => ['webbook.mall.manage_price_categories'],
                'keywords'    => 'shop store mall currency price categories',
            ],
            'tax_settings'              => [
                'label'       => 'webbook.mall::lang.common.taxes',
                'description' => 'webbook.mall::lang.tax_settings.description',
                'category'    => 'webbook.mall::lang.general_settings.category',
                'icon'        => 'icon-percent',
                'url'         => Backend::url('webbook/mall/taxes'),
                'order'       => 40,
                'permissions' => ['webbook.mall.manage_taxes'],
                'keywords'    => 'shop store mall tax taxes',
            ],
            'notification_settings'     => [
                'label'       => 'webbook.mall::lang.notification_settings.label',
                'description' => 'webbook.mall::lang.notification_settings.description',
                'category'    => 'webbook.mall::lang.general_settings.category',
                'icon'        => 'icon-envelope',
                'url'         => Backend::url('webbook/mall/notifications'),
                'order'       => 40,
                'permissions' => ['webbook.mall.manage_notifications'],
                'keywords'    => 'shop store mall notifications email mail',
            ],
            'feed_settings'             => [
                'label'       => 'webbook.mall::lang.common.feeds',
                'description' => 'webbook.mall::lang.feed_settings.description',
                'category'    => 'webbook.mall::lang.general_settings.category',
                'icon'        => 'icon-rss',
                'class'       => FeedSettings::class,
                'order'       => 50,
                'permissions' => ['webbook.mall.manage_feeds'],
                'keywords'    => 'shop store mall feeds',
            ],
            'review_settings'           => [
                'label'       => 'webbook.mall::lang.common.reviews',
                'description' => 'webbook.mall::lang.review_settings.description',
                'category'    => 'webbook.mall::lang.general_settings.category',
                'icon'        => 'icon-star',
                'class'       => ReviewSettings::class,
                'order'       => 60,
                'permissions' => ['webbook.mall.manage_reviews'],
                'keywords'    => 'shop store mall reviews',
            ],
            'payment_gateways_settings' => [
                'label'       => 'webbook.mall::lang.payment_gateway_settings.label',
                'description' => 'webbook.mall::lang.payment_gateway_settings.description',
                'category'    => 'webbook.mall::lang.general_settings.category_orders',
                'icon'        => 'icon-credit-card',
                'class'       => PaymentGatewaySettings::class,
                'order'       => 30,
                'permissions' => ['webbook.mall.settings.manage_payment_gateways'],
                'keywords'    => 'shop store mall payment gateways',
            ],
            'payment_method_settings'   => [
                'label'       => 'webbook.mall::lang.common.payment_methods',
                'description' => 'webbook.mall::lang.payment_method_settings.description',
                'category'    => 'webbook.mall::lang.general_settings.category_orders',
                'icon'        => 'icon-money',
                'url'         => Backend::url('webbook/mall/paymentmethods'),
                'order'       => 40,
                'permissions' => ['webbook.mall.settings.manage_payment_methods'],
                'keywords'    => 'shop store mall payment methods',
            ],
            'shipping_method_settings'  => [
                'label'       => 'webbook.mall::lang.common.shipping_methods',
                'description' => 'webbook.mall::lang.shipping_method_settings.description',
                'category'    => 'webbook.mall::lang.general_settings.category_orders',
                'icon'        => 'icon-truck',
                'url'         => Backend::url('webbook/mall/shippingmethods'),
                'order'       => 40,
                'permissions' => ['webbook.mall.manage_shipping_methods'],
                'keywords'    => 'shop store mall shipping methods',
            ],
            'order_state_settings'      => [
                'label'       => 'webbook.mall::lang.common.order_states',
                'description' => 'webbook.mall::lang.order_state_settings.description',
                'category'    => 'webbook.mall::lang.general_settings.category_orders',
                'icon'        => 'icon-history',
                'url'         => Backend::url('webbook/mall/orderstate'),
                'order'       => 50,
                'permissions' => ['webbook.mall.manage_order_states'],
                'keywords'    => 'shop store mall notifications email mail',
            ],
        ];
    }
}
