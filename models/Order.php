<?php

declare(strict_types=1);

namespace WebBook\Mall\Models;

use Barryvdh\DomPDF\PDF;
use Carbon\Carbon;
use DB;
use Event;
use Illuminate\Support\Facades\Queue;
use Model;
use October\Rain\Database\Traits\SoftDelete;
use October\Rain\Database\Traits\Validation;
use October\Rain\Exception\ValidationException;
use WebBook\Mall\Classes\Exceptions\InvalidDiscountException;
use WebBook\Mall\Classes\Jobs\SendVirtualProductFiles;
use WebBook\Mall\Classes\PaymentState\PaidState;
use WebBook\Mall\Classes\PaymentState\PendingState;
use WebBook\Mall\Classes\Traits\HashIds;
use WebBook\Mall\Classes\Traits\JsonPrice;
use WebBook\Mall\Classes\Traits\PDFMaker;
use WebBook\Mall\Classes\Utils\Money;
use RuntimeException;
use Session;
use System\Classes\PluginManager;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Order extends Model
{
    use Validation;
    use SoftDelete;
    use JsonPrice {
        useCurrency as fallbackCurrency;
    }
    use HashIds;
    use PDFMaker;

    public $rules = [
        'currency'                         => 'required',
        'shipping_address_same_as_billing' => 'required|boolean',
        'billing_address'                  => 'required',
        'lang'                             => 'required',
        'ip_address'                       => 'required',
        'customer_id'                      => 'required|exists:webbook_mall_customers,id',
    ];

    public $jsonable = [
        'billing_address',
        'shipping_address',
        'custom_fields',
        'taxes',
        'payment',
        'currency',
        'discounts',
        'shipping',
    ];

    public $table = 'webbook_mall_orders';

    public $hasOne = ['payment_log' => PaymentLog::class];

    public $hasMany = [
        'products'         => OrderProduct::class,
        'virtual_products' => [OrderProduct::class, 'scope' => 'virtual'],
        'payment_logs'     => [PaymentLog::class, 'order' => 'created_at DESC'],
    ];

    public $belongsTo = [
        'payment_method'          => [
            PaymentMethod::class,
            'deleted'   => true,
            'scope'     => 'all',
        ],
        'customer_payment_method' => [
            CustomerPaymentMethod::class,
            'deleted'   => true,
        ],
        'order_state'             => [
            OrderState::class,
            'deleted'   => true,
            'scope'     => 'all',
        ],
        'customer'                => [Customer::class, 'deleted' => true],
        'cart'                    => [Cart::class, 'deleted' => true],
    ];

    public $casts = [
        'shipping_address_same_as_billing' => 'boolean',
    ];

    /**
     * Use to define if the shipping notification should be sent.
     * @var bool
     */
    public $shippingNotification = false;

    /**
     * Use to define if the state change notification should be sent.
     * @var bool
     */
    public $stateNotification = true;

    protected $dates = ['deleted_at', 'shipped_at', 'paid_at'];

    public function beforeCreate()
    {
        if (! $this->order_number) {
            $this->setOrderNumber();
        }
        $this->payment_hash = str_random(10);
    }

    public function afterUpdate()
    {
        if ($this->isDirty('payment_state')) {
            // Don't trigger payment changes during the checkout flow. A mall.checkout.succeeded
            // Event will already be triggered in the PaymentRedirector.
            $flow = session()->get('mall.checkout.flow');

            if ($flow !== 'checkout') {
                Event::fire('mall.order.payment_state.changed', [$this]);
            }

            // If the order became paid, distribute all virtual products.
            if ($this->payment_state === PaidState::class && $this->paid_at === null) {
                if ($this->virtual_products->count() > 0) {
                    Queue::push(SendVirtualProductFiles::class, ['order' => $this->id]);
                }
                $this->paid_at = Carbon::today();
                $this->saveQuietly();
            }
        }

        if ($this->isDirty('order_state_id')) {
            Event::fire('mall.order.state.changed', [$this]);
        }

        if ($this->isDirty('tracking_url') || $this->isDirty('tracking_number')) {
            Event::fire('mall.order.tracking.changed', [$this]);
        }

        if ($this->getOriginal('shipped_at') === null && $this->isDirty('shipped_at')) {
            Event::fire('mall.order.shipped', [$this]);
        }
    }

    public function afterDelete()
    {
        $this->products->each->delete();
        $this->payment_logs->each->delete();

        if ($this->cart) {
            $this->cart->delete();
        }
    }

    public function getIsShippedAttribute()
    {
        return $this->shipped_at !== null;
    }

    public function getIsCancelledAttribute()
    {
        return $this->order_state->flag === OrderState::FLAG_CANCELLED;
    }

    public static function byCustomer(Customer $customer)
    {
        return static::where('customer_id', $customer->id);
    }

    public static function fromCart(Cart $cart, array $additionalAttributes = []): self
    {
        $cart->loadMissing(['products.product.brand']);

        // Make sure all products in the cart are still published.
        $removed = $cart->removeUnpublishedProducts();

        if ($removed->count() > 0) {
            throw new ValidationException(['cart' => trans('webbook.mall::frontend.cart.products_unavailable')]);
        }

        // Ensure all applied discounts are still valid.
        try {
            $cart->validateDiscounts();
        } catch (InvalidDiscountException $e) {
            throw new ValidationException(['cart' => trans('webbook.mall::frontend.cart.discounts_no_longer_valid')]);
        }

        $order = DB::transaction(function () use ($additionalAttributes, $cart) {
            Event::fire('mall.order.beforeCreate', [$cart]);

            $initialOrderStatus = OrderState::where('flag', OrderState::FLAG_NEW)->first();

            if (! $initialOrderStatus) {
                throw new RuntimeException('You have to create an order state with the "new" flag before accepting orders!');
            }

            if ($cart->products->count() < 1) {
                throw new ValidationException(['Your order is empty. Please add a product to the cart.']);
            }

            $cart->validateShippingMethod();

            if ($cart->shipping_method_id === null && ! $cart->is_virtual) {
                throw new ValidationException(['Your order has no shipping method set. Please select a shipping method.']);
            }

            $totals = $cart->totals;

            $order                                          = new static();
            $order->session_id                              = session()->getId();
            $order->currency                                = Currency::activeCurrency();
            $order->lang                                    = $order->getLocale();
            $order->shipping_address_same_as_billing        = $cart->shipping_address_same_as_billing;
            $order->billing_address                         = $cart->billing_address;
            $order->shipping_address                        = $cart->shipping_address;
            $order->shipping                                = $totals->shippingTotal();
            $order->payment                                 = $totals->paymentTotal();
            $order->taxes                                   = $totals->taxes();
            $order->discounts                               = $totals->appliedDiscounts();
            $order->ip_address                              = request()->ip();
            $order->customer_id                             = $cart->customer->id;
            $order->payment_method_id                       = $cart->payment_method_id;
            $order->customer_payment_method_id              = $cart->customer_payment_method_id;
            $order->payment_state                           = PendingState::class;
            $order->order_state_id                          = $initialOrderStatus->id;
            $order->is_virtual                              = $cart->is_virtual;

            $order->attributes['total_shipping_pre_taxes']  = $order->round($totals->shippingTotal()->totalPreTaxes());
            $order->attributes['total_shipping_taxes']      = $order->round($totals->shippingTotal()->totalTaxes());
            $order->attributes['total_shipping_post_taxes'] = $order->round($totals->shippingTotal()->totalPostTaxes());
            $order->attributes['total_payment_pre_taxes']   = $order->round($totals->paymentTotal()->totalPreTaxes());
            $order->attributes['total_payment_taxes']       = $order->round($totals->paymentTotal()->totalTaxes());
            $order->attributes['total_payment_post_taxes']  = $order->round($totals->paymentTotal()->totalPostTaxes());
            $order->attributes['total_product_pre_taxes']   = $order->round($totals->productPreTaxes());
            $order->attributes['total_product_taxes']       = $order->round($totals->productTaxes());
            $order->attributes['total_product_post_taxes']  = $order->round($totals->productPostTaxes());
            $order->attributes['total_pre_payment']         = $order->round($totals->totalPrePayment());
            $order->attributes['total_pre_taxes']           = $order->round($totals->totalPreTaxes());
            $order->attributes['total_taxes']               = $order->round($totals->totalTaxes());
            $order->attributes['total_post_taxes']          = $order->round($totals->totalPostTaxes());
            $order->total_weight                            = $order->round($totals->weightTotal());

            $order->forceFill($additionalAttributes);

            $order->save();

            $cart
                ->products
                ->each(function (CartProduct $entry) use ($order) {
                    $entry->moveToOrder($order);
                });

            Event::fire('mall.order.afterCreate', [$order, $cart]);

            $cart->updateDiscountUsageCount();

            $cart->delete(); // We can empty the cart once the order is created.

            return $order;
        });

        // Drop any saved payment information since the order has been
        // created successfully.
        Session::forget('mall.payment_method.data');

        // Remove any enforced shipping state.
        Session::forget('mall.shipping.enforced.price');
        Session::forget('mall.shipping.enforced.name');

        Event::fire('mall.order.created', [$order]);

        return $order;
    }

    /**
     * Returns the pdf invoice for this order.
     * If no invoice is available false is returned.
     *
     * @throws \Cms\Classes\CmsException
     * @return PDF|bool
     */
    public function getPDFInvoice()
    {
        if ($this->payment_method->pdf_partial) {
            return $this->makePDFFromDir($this->payment_method->pdf_partial, ['order' => $this]);
        }

        return false;
    }

    public function getPriceColumns(): array
    {
        return [
            'total_shipping_pre_taxes',
            'total_shipping_taxes',
            'total_shipping_post_taxes',
            'total_payment_pre_taxes',
            'total_payment_taxes',
            'total_payment_post_taxes',
            'total_product_pre_taxes',
            'total_product_taxes',
            'total_product_post_taxes',
            'total_taxes',
            'total_post_taxes',
            'total_pre_taxes',
        ];
    }

    /**
     * Returns the amount of the order in the selected currency.
     * This is used in the PaymentProvider classes.
     *
     * @return float
     */
    public function getTotalInCurrencyAttribute()
    {
        $total = (int)$this->getOriginal('total_post_taxes');

        return app(Money::class)->round($total, $this->currency['decimals']);
    }

    public function getPaymentStateLabelAttribute()
    {
        return $this->payment_state::label();
    }

    public function getOrderStateLabelAttribute()
    {
        return $this->order_state->name;
    }

    public function getShippingAddressStringAttribute()
    {
        return implode("\n", $this->shipping_address);
    }

    public function getIsPaidAttribute()
    {
        return $this->payment_state === PaidState::class;
    }

    public function totalPreTaxes()
    {
        return $this->toPriceModel('total_pre_taxes');
    }

    public function totalTaxes()
    {
        return $this->toPriceModel('total_taxes');
    }

    public function totalPostTaxes()
    {
        return $this->toPriceModel('total_post_taxes');
    }

    public function totalProductPreTaxes()
    {
        return $this->toPriceModel('total_product_pre_taxes');
    }

    public function totalProductTaxes()
    {
        return $this->toPriceModel('total_product_taxes');
    }

    public function totalProductPostTaxes()
    {
        return $this->toPriceModel('total_product_post_taxes');
    }

    public function totalShippingPreTaxes()
    {
        return $this->toPriceModel('total_shipping_pre_taxes');
    }

    public function totalShippingTaxes()
    {
        return $this->toPriceModel('total_shipping_taxes');
    }

    public function totalShippingPostTaxes()
    {
        return $this->toPriceModel('total_shipping_post_taxes');
    }

    public function totalPaymentPreTaxes()
    {
        return $this->toPriceModel('total_payment_pre_taxes');
    }

    public function totalPaymentTaxes()
    {
        return $this->toPriceModel('total_payment_taxes');
    }

    public function totalPaymentPostTaxes()
    {
        return $this->toPriceModel('total_payment_post_taxes');
    }

    /**
     * Cleanup of old data using OFFLINE.GDPR.
     *
     * @see https://github.com/OFFLINE-GmbH/oc-gdpr-plugin
     *
     * @param Carbon $deadline
     * @param int $keepDays
     */
    public function gdprCleanup(Carbon $deadline, int $keepDays)
    {
        self::where('created_at', '<', $deadline)
            ->withTrashed()
            ->whereHas('order_state', function ($q) {
                $q->where('flag', OrderState::FLAG_COMPLETE);
            })
            ->get()
            ->each(function (Order $order) {
                DB::transaction(function () use ($order) {
                    $order->forceDelete();
                });
            });
    }

    /**
     * This is here to provide custom rounding options for the
     * end-user in future versions (like round to .05)
     * @param mixed $amount
     */
    protected function round($amount)
    {
        return round($amount);
    }

    /**
     * Sets the order number to the next higher value.
     */
    protected function setOrderNumber()
    {
        $numbers = DB::table($this->getTable())
            ->sharedLock()
            ->selectRaw('max(cast(order_number as unsigned)) as max')
            ->first();

        $start = $numbers->max;

        if (is_null($start) || $start === 0) {
            $start = (int)GeneralSettings::get('order_start');
        }

        $this->order_number = $start + 1;
    }

    protected function useCurrency()
    {
        if ($this->currency) {
            return new Currency($this->currency);
        }

        return $this->fallbackCurrency();
    }

    protected function toPriceModel(string $key): Price
    {
        return new Price([
            'currency_id' => $this->useCurrency()->id,
            'price'       => $this->getOriginal($key) / 100,
        ]);
    }

    protected function getLocale()
    {
        if (PluginManager::instance()->exists('RainLab.Translate')) {
            return \RainLab\Translate\Classes\Translator::instance()->getLocale();
        }

        return 'default';
    }
}
