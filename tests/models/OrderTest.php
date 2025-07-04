<?php

declare(strict_types=1);

namespace WebBook\Mall\Tests\Models;

use Event;
use WebBook\Mall\Classes\Exceptions\OutOfStockException;
use WebBook\Mall\Classes\PaymentState\PendingState;
use WebBook\Mall\Classes\User\Auth;
use WebBook\Mall\Models\Address;
use WebBook\Mall\Models\Cart;
use WebBook\Mall\Models\Customer;
use WebBook\Mall\Models\CustomField;
use WebBook\Mall\Models\CustomFieldOption;
use WebBook\Mall\Models\CustomFieldValue;
use WebBook\Mall\Models\Discount;
use WebBook\Mall\Models\Order;
use WebBook\Mall\Models\OrderState;
use WebBook\Mall\Models\Price;
use WebBook\Mall\Models\Product;
use WebBook\Mall\Models\ShippingMethod;
use WebBook\Mall\Models\Tax;
use WebBook\Mall\Models\Variant;
use WebBook\Mall\Tests\PluginTestCase;
use RainLab\User\Models\User;

class OrderTest extends PluginTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Auth::login(User::first());

        // Set Country
        Event::listen('mall.cart.setCountry', function ($model) {
            $model->countryId = 14;
        });
    }

    public function test_it_creates_a_new_order_from_a_cart()
    {
        $cart                      = $this->getFullCart();
        $cart->shipping_address_id = 2;
        $cart->billing_address_id  = 1;
        $cart->save();

        $order = Order::fromCart($cart);
        $order->save();

        $this->assertEquals(1, $order->order_number);
        $this->assertEquals(PendingState::class, $order->payment_state);
        $this->assertEquals(OrderState::where('flag', OrderState::FLAG_NEW)->first()->id, $order->order_state_id);
        $this->assertEquals(76.92, $order->total_shipping_pre_taxes);
        $this->assertEquals(23.07, $order->total_shipping_taxes);
        $this->assertEquals(100.00, $order->total_shipping_post_taxes);

        $this->assertEquals(923.07, $order->total_product_pre_taxes);
        $this->assertEquals(1200.00, $order->total_product_post_taxes);
        $this->assertEquals(999.99, $order->total_pre_taxes);
        $this->assertEquals(299.99, $order->total_taxes);
        $this->assertEquals(1300.00, $order->total_post_taxes);
        $this->assertEquals(2800, $order->total_weight);

        $this->assertNotEmpty($order->ip_address);

        $this->assertFalse($order->shipping_address_same_as_billing);
        $this->assertEquals(json_encode(Address::find(1)), $order->getOriginal('billing_address'));
        $this->assertEquals(json_encode(Address::find(2)), $order->getOriginal('shipping_address'));

        $this->assertNotNull($cart->deleted_at);
    }

    public function test_it_updates_product_stock()
    {
        $product        = Product::first();
        $product->price = ['CHF' => 200, 'EUR' => 300];
        $product->stock = 10;
        $product->save();

        $cart = $this->getSimpleCart();
        $cart->addProduct($product, 2);

        $order = Order::fromCart($cart);
        $order->save();

        $this->assertEquals(8, $product->fresh()->stock);
    }

    public function test_it_prevents_out_of_stock_purchase()
    {
        $this->expectException(OutOfStockException::class);

        $product                               = Product::first();
        $product->price                        = ['CHF' => 200, 'EUR' => 300];
        $product->stock                        = 10;
        $product->allow_out_of_stock_purchases = false;
        $product->save();

        $cart = $this->getSimpleCart();
        $cart->addProduct($product, 12);

        $order = Order::fromCart($cart);
        $order->save();

        $this->assertEquals(10, $product->fresh()->stock);
    }

    public function test_it_allows_explicit_out_of_stock_purchase()
    {
        $product                               = Product::first();
        $product->price                        = ['CHF' => 200, 'EUR' => 300];
        $product->stock                        = 10;
        $product->allow_out_of_stock_purchases = true;
        $product->save();

        $cart = $this->getSimpleCart();
        $cart->addProduct($product, 12);

        $order = Order::fromCart($cart);
        $order->save();

        $this->assertEquals(-2, $product->fresh()->stock);
    }

    public function test_it_updates_variant_stock()
    {
        $product        = Product::first();
        $product->price = ['CHF' => 200, 'EUR' => 300];
        $product->stock = 10;
        $product->save();

        $variant             = new Variant();
        $variant->name       = 'Variant';
        $variant->product_id = $product->id;
        $variant->stock      = 20;
        $variant->save();

        $cart = $this->getSimpleCart();
        $cart->addProduct($product, 2, $variant);

        $order = Order::fromCart($cart);
        $order->save();

        $this->assertEquals(10, $product->fresh()->stock);
        $this->assertEquals(18, $variant->fresh()->stock);
    }

    public function test_it_prevents_out_of_stock_variant_purchase()
    {
        $this->expectException(OutOfStockException::class);

        $product        = Product::first();
        $product->price = ['CHF' => 200, 'EUR' => 300];
        $product->stock = 10;
        $product->save();

        $variant                               = new Variant();
        $variant->name                         = 'Variant';
        $variant->product_id                   = $product->id;
        $variant->stock                        = 20;
        $product->allow_out_of_stock_purchases = false;
        $variant->save();

        $cart = $this->getSimpleCart();
        $cart->addProduct($product, 21, $variant);

        $order = Order::fromCart($cart);
        $order->save();

        $this->assertEquals(10, $product->fresh()->stock);
        $this->assertEquals(20, $variant->fresh()->stock);
    }

    public function test_it_allows_explicit_out_of_stock_variant_purchase()
    {
        $this->expectException(OutOfStockException::class);

        $product        = Product::first();
        $product->price = ['CHF' => 200, 'EUR' => 300];
        $product->stock = 10;
        $product->save();

        $variant                               = new Variant();
        $variant->name                         = 'Variant';
        $variant->product_id                   = $product->id;
        $variant->stock                        = 20;
        $product->allow_out_of_stock_purchases = true;
        $variant->save();

        $cart = $this->getSimpleCart();
        $cart->addProduct($product, 21, $variant);

        $order = Order::fromCart($cart);
        $order->save();

        $this->assertEquals(10, $product->fresh()->stock);
        $this->assertEquals(-1, $variant->fresh()->stock);
    }

    public function test_it_uses_the_correct_discounted_shipping_method()
    {
        $cart   = $this->getSimpleCart(true);
        $method = ShippingMethod::find($cart->shipping_method->id);

        $this->assertEquals(1, $method->id);
        $this->assertEquals('Standard', $method->name);

        $discount                       = new Discount();
        $discount->name                 = 'Shipping Test';
        $discount->type                 = 'shipping';
        $discount->trigger              = 'code';
        $discount->code                 = 'SHIPPING';
        $discount->shipping_description = 'Reduced shipping';
        $discount->save();

        $discount->shipping_prices()->save(new Price([
            'currency_id' => 2,
            'price'       => 10,
            'field'       => 'shipping_prices',
        ]));

        $cart->applyDiscount($discount);

        $order = Order::fromCart($cart);
        $this->assertEquals($discount->shipping_description, $order->shipping['method']['name']);
        $this->assertEquals($discount->shippingPrice()->integer, $order->shipping['total']);
        $this->assertEquals(
            $discount->shippingPrice()->integer,
            $order->shipping['method']['price']['CHF']['price']
        );
    }

    public function test_discount_number_of_usages_gets_updated()
    {
        $cart                            = $this->getSimpleCart(true);
        $discount1                       = new Discount();
        $discount1->name                 = 'Shipping Test';
        $discount1->type                 = 'shipping';
        $discount1->trigger              = 'total';
        $discount1->shipping_description = 'Reduced shipping';
        $discount1->number_of_usages     = 10;
        $discount1->save();

        $discount1->shipping_prices()->save(new Price([
            'currency_id' => 2,
            'price'       => 10,
            'field'       => 'shipping_prices',
        ]));
        $discount1->totals_to_reach()->save(new Price([
            'currency_id' => 2,
            'price'       => 0,
            'field'       => 'total_to_reach',
        ]));

        $discount2                   = new Discount();
        $discount2->name             = 'Amount Test';
        $discount2->type             = 'fixed_amount';
        $discount2->trigger          = 'code';
        $discount2->code             = 'TEST';
        $discount2->number_of_usages = 12;
        $discount2->save();

        $discount2->amounts()->save(new Price([
            'currency_id' => 2,
            'price'       => 20,
            'field'       => 'amounts',
        ]));

        $discount3                   = new Discount();
        $discount3->name             = 'Amount Test';
        $discount3->type             = 'rate';
        $discount3->rate             = 20;
        $discount3->trigger          = 'code';
        $discount3->number_of_usages = 18;
        $discount3->save();

        $cart->applyDiscount($discount2);
        $cart->applyDiscount($discount3);

        Order::fromCart($cart);

        $this->assertEquals(10, $discount1->fresh()->number_of_usages);
        $this->assertEquals(13, $discount2->fresh()->number_of_usages);
        $this->assertEquals(19, $discount3->fresh()->number_of_usages);
    }

    protected function getFullCart(): Cart
    {
        $tax1             = new Tax();
        $tax1->name       = 'Tax 1';
        $tax1->percentage = 10;
        $tax1->save();
        $tax2             = new Tax();
        $tax2->name       = 'Tax 2';
        $tax2->percentage = 20;
        $tax2->save();

        $productA                     = Product::first();
        $productA->stackable          = true;
        $productA->weight             = 400;
        $productA->stock              = 10;
        $productA->price_includes_tax = true;
        $productA->save();
        $productA->price = ['CHF' => 200, 'EUR' => 300];
        $productA->taxes()->attach([$tax1->id, $tax2->id]);
        $productA->save();
        $productA = Product::find($productA->id);

        $productB                     = new Product();
        $productB->name               = 'Another Product';
        $productB->stock              = 10;
        $productB->weight             = 800;
        $productB->published          = true;
        $productB->price_includes_tax = true;
        $productB->save();
        $productB->price = ['CHF' => 100, 'EUR' => 300];
        $productB->taxes()->attach([$tax1->id, $tax2->id]);
        $productB = Product::find($productB->id);

        $sizeA             = new CustomFieldOption();
        $sizeA->name       = 'Size A';
        $sizeA->sort_order = 1;
        $sizeA->save();
        $sizeA->prices()->save(new Price([
            'price'       => 100,
            'currency_id' => 2,
        ]));

        $sizeB             = new CustomFieldOption();
        $sizeB->name       = 'Size B';
        $sizeB->sort_order = 1;
        $sizeB->save();
        $sizeB->prices()->save(new Price([
            'price'       => 200,
            'currency_id' => 2,
        ]));

        $field       = new CustomField();
        $field->name = 'Size';
        $field->type = 'dropdown';
        $field->save();

        $field->custom_field_options()->save($sizeA);
        $field->custom_field_options()->save($sizeB);

        $productA->custom_fields()->attach($field);

        $customFieldValueA                         = new CustomFieldValue();
        $customFieldValueA->custom_field_id        = $field->id;
        $customFieldValueA->custom_field_option_id = $sizeA->id;

        $customFieldValueB                         = new CustomFieldValue();
        $customFieldValueB->custom_field_id        = $field->id;
        $customFieldValueB->custom_field_option_id = $sizeB->id;

        $cart = new Cart();
        $cart->addProduct($productA, 2, null, collect([$customFieldValueA]));
        $cart->addProduct($productA, 1, null, collect([$customFieldValueB]));
        $cart->addProduct($productB, 2);

        $shippingMethod = ShippingMethod::first();
        $shippingMethod->save();
        $shippingMethod->price = ['CHF' => 100, 'EUR' => 300];

        $shippingMethod->taxes()->attach([$tax1->id, $tax2->id]);

        $cart->setShippingMethod($shippingMethod);

        $cart->setCustomer(Customer::first());

        $cart->setBillingAddress(Address::find(1));
        $cart->setShippingAddress(Address::find(2));

        return $cart;
    }

    protected function getSimpleCart($withProduct = false): Cart
    {
        $cart = new Cart();

        if ($withProduct) {
            $product                     = Product::first();
            $product->stackable          = true;
            $product->weight             = 400;
            $product->stock              = 10;
            $product->price_includes_tax = true;
            $product->save();
            $product->price = ['CHF' => 200, 'EUR' => 300];

            $product = Product::first();

            $cart->addProduct($product, 2);
        }

        $cart->setShippingMethod(ShippingMethod::first());
        $cart->setCustomer(Customer::first());
        $cart->setBillingAddress(Address::find(1));
        $cart->setShippingAddress(Address::find(2));

        return $cart;
    }
}
