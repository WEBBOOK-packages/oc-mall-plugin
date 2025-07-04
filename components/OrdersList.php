<?php

declare(strict_types=1);

namespace WebBook\Mall\Components;

use Auth;
use Illuminate\Support\Collection;
use WebBook\Mall\Models\GeneralSettings;
use WebBook\Mall\Models\Order;
use WebBook\Mall\Models\OrderState;

/**
 * The OrdersList component displays a list of all the user's orders.
 */
class OrdersList extends MallComponent
{
    /**
     * Array of all orders.
     *
     * @var Collection
     */
    public $orders;

    /**
     * All available countries.
     *
     * @var Collection
     */
    public $countries;

    /**
     * Link to pay a pending order.
     *
     * @var string
     */
    public $paymentLink;

    /**
     * Component details.
     *
     * @return array
     */
    public function componentDetails()
    {
        return [
            'name'        => 'webbook.mall::lang.components.ordersList.details.name',
            'description' => 'webbook.mall::lang.components.ordersList.details.description',
        ];
    }

    /**
     * Properties of this component.
     *
     * @return array
     */
    public function defineProperties()
    {
        return [];
    }

    /**
     * The component is initialized.
     *
     * @return void
     */
    public function init()
    {
        $user = Auth::user();

        if (!$user || !$user->customer) {
            return;
        }

        $this->paymentLink = $this->getPaymentLink();
        $this->orders = Order::byCustomer($user->customer)
            ->with(['products', 'products.variant'])
            ->orderBy('created_at', 'DESC')
            ->get();
    }

    public function onCancelOrder()
    {
        $user = Auth::user();

        if (! $user) {
            return;
        }

        $state = OrderState::where('flag', OrderState::FLAG_CANCELLED)->first();

        $order = Order::byCustomer($user->customer)->where('id', post('id'))->firstOrFail();
        $order->order_state = $state;
        $order->save();
    }

    /**
     * Get the URL of the payment page.
     *
     * @return string
     */
    protected function getPaymentLink()
    {
        $page = GeneralSettings::get('checkout_page');

        return $this->controller->pageUrl($page, ['step' => 'payment']);
    }
}
