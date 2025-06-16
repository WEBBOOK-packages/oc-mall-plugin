<?php

namespace WebBook\Mall\Classes\Registration;

use WebBook\Mall\Components\AddressForm;
use WebBook\Mall\Components\AddressList;
use WebBook\Mall\Components\AddressSelector;
use WebBook\Mall\Components\Cart;
use WebBook\Mall\Components\Checkout;
use WebBook\Mall\Components\CurrencyPicker;
use WebBook\Mall\Components\CustomerProfile;
use WebBook\Mall\Components\DiscountApplier;
use WebBook\Mall\Components\EnhancedEcommerceAnalytics;
use WebBook\Mall\Components\MallDependencies;
use WebBook\Mall\Components\MyAccount;
use WebBook\Mall\Components\OrdersList;
use WebBook\Mall\Components\PaymentMethodSelector;
use WebBook\Mall\Components\Product as ProductComponent;
use WebBook\Mall\Components\ProductReviews;
use WebBook\Mall\Components\Products as ProductsComponent;
use WebBook\Mall\Components\ProductsFilter;
use WebBook\Mall\Components\QuickCheckout;
use WebBook\Mall\Components\ShippingMethodSelector;
use WebBook\Mall\Components\SignUp;
use WebBook\Mall\Components\WishlistButton;
use WebBook\Mall\Components\Wishlists;
use WebBook\Mall\FormWidgets\Price;
use WebBook\Mall\FormWidgets\PropertyFields;

trait BootComponents
{
    public function registerComponents()
    {
        return [
            Cart::class                       => 'cart',
            SignUp::class                     => 'signUp',
            ShippingMethodSelector::class     => 'shippingMethodSelector',
            AddressSelector::class            => 'addressSelector',
            AddressForm::class                => 'addressForm',
            PaymentMethodSelector::class      => 'paymentMethodSelector',
            Checkout::class                   => 'checkout',
            QuickCheckout::class              => 'quickCheckout',
            ProductsComponent::class          => 'products',
            ProductsFilter::class             => 'productsFilter',
            ProductComponent::class           => 'product',
            DiscountApplier::class            => 'discountApplier',
            MyAccount::class                  => 'myAccount',
            OrdersList::class                 => 'ordersList',
            CustomerProfile::class            => 'customerProfile',
            AddressList::class                => 'addressList',
            CurrencyPicker::class             => 'currencyPicker',
            MallDependencies::class           => 'mallDependencies',
            EnhancedEcommerceAnalytics::class => 'enhancedEcommerceAnalytics',
            Wishlists::class                  => 'wishlists',
            WishlistButton::class             => 'wishlistButton',
            ProductReviews::class             => 'productReviews',
        ];
    }

    public function registerFormWidgets()
    {
        return [
            PropertyFields::class => 'mall.propertyfields',
            Price::class          => 'mall.price',
        ];
    }
}
