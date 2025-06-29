<?php

declare(strict_types=1);

namespace WebBook\Mall\Components;

use Auth;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Collection;
use October\Rain\Exception\ValidationException;
use WebBook\Mall\Classes\Payments\PaymentGateway;
use WebBook\Mall\Classes\Payments\PaymentService;
use WebBook\Mall\Classes\Traits\HashIds;
use WebBook\Mall\Models\Cart;
use WebBook\Mall\Models\CustomerPaymentMethod;
use WebBook\Mall\Models\GeneralSettings;
use WebBook\Mall\Models\Order;
use WebBook\Mall\Models\PaymentMethod;
use Symfony\Component\HttpFoundation\Response;
use Validator;

/**
 * The PaymentMethodSelector component allows the user
 * to select a payment method during checkout.
 */
class PaymentMethodSelector extends MallComponent
{
    use HashIds;

    /**
     * The user's cart.
     *
     * @var Cart
     */
    public $cart;

    /**
     * The active payment method.
     *
     * @var PaymentMethod
     */
    public $activeMethod;

    /**
     * Payment data.
     *
     * @var Collection
     */
    public $paymentData;

    /**
     * All available PaymentMethods
     * @var Collection
     */
    public $methods;

    /**
     * All available CustomerPaymentMethods
     * @var Collection
     */
    public $customerMethods;

    /**
     * The current order.
     *
     * @var Order
     */
    public $order;

    /**
     * Depending on whether the order is paid during checkout
     * or later on the component is working on either the Order
     * or the Cart model.
     *
     * @var Order|Cart
     */
    public $workingOnModel;

    /**
     * Backend setting whether shipping should be before payment.
     *
     * @var bool
     */
    public $shippingSelectionBeforePayment = false;

    /**
     * Component details.
     *
     * @return array
     */
    public function componentDetails()
    {
        return [
            'name'        => 'webbook.mall::lang.components.paymentMethodSelector.details.name',
            'description' => 'webbook.mall::lang.components.paymentMethodSelector.details.description',
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
     * The component is executed.
     *
     * @return string|void
     */
    public function onRun()
    {
        return $this->setData();
    }

    /**
     * The user has selected a payment method.
     *
     * Any specified payment data is stored in the session.
     *
     * @throws \Cms\Classes\CmsException
     * @return Response
     */
    public function onSubmit()
    {
        $this->setData();
        $data = post('payment_data', []);

        // Create the payment gateway to trigger the validation.
        // If not all specified data is valid an exception is thrown here.
        $gateway = app(PaymentGateway::class);
        $gateway->init($this->getPaymentMethod(), $data);

        // When the user hits "submit" no customer payment method was selected
        // so make sure to remove the information for the cart or order
        // in case it sits there from a previous payment attempt.
        $this->workingOnModel->customer_payment_method_id = null;
        $this->workingOnModel->save();

        return $this->doRedirect($gateway, $data);
    }

    /**
     * A different payment method has been selected.
     *
     * @throws ValidationException
     * @return array
     */
    public function onChangeMethod()
    {
        $this->setData();

        $rules = [
            'id' => 'required|exists:webbook_mall_payment_methods,id',
        ];

        $validation = Validator::make(post(), $rules);

        if ($validation->fails()) {
            throw new ValidationException($validation);
        }

        $id = post('id');

        $this->workingOnModel->payment_method_id = $id;
        $this->workingOnModel->save();

        $this->setData();

        return [
            '.mall-payment-method-selector' => $this->renderPartial($this->alias . '::selector'),
            'method'                        => PaymentMethod::where('id', $id)->first(),
        ];
    }

    /**
     * The customer proceeds with a saved payment method.
     */
    public function onUseCustomerPaymentMethod()
    {
        $this->setData();
        $id = $this->decode(post('id'));

        $method = CustomerPaymentMethod::where('customer_id', $this->workingOnModel->customer->id)
            ->where('id', $id)->first();

        if (! $method) {
            throw new ValidationException([
                'customer_method' => trans('customer_payment_method.does_not_exist'),
            ]);
        }

        $this->workingOnModel->payment_method_id          = $method->payment_method_id;
        $this->workingOnModel->customer_payment_method_id = $method->id;
        $this->workingOnModel->save();

        $data = ['use_customer_payment_method' => true];

        $gateway = app(PaymentGateway::class);
        $gateway->init($this->getPaymentMethod(), $data);

        return $this->doRedirect($gateway, $data);
    }

    /**
     * Renders the payment form of the currently selected
     * payment method.
     *
     * @return string
     */
    public function renderPaymentForm()
    {
        if (! $this->workingOnModel->payment_method) {
            return '';
        }

        /** @var PaymentGateway $gateway */
        $gateway = app(PaymentGateway::class);

        return $gateway
            ->getProviderById($this->workingOnModel->payment_method->payment_provider)
            ->renderPaymentForm($this->workingOnModel);
    }

    /**
     * This method sets all variables needed for this component to work.
     *
     * @return void
     */
    protected function setData()
    {
        $user = Auth::user();

        if (! $user) {
            return;
        }

        $this->setVar('cart', Cart::byUser($user));
        $this->workingOnModel = $this->cart;

        if ($orderId = request()->get('order')) {
            $orderId = $this->decode($orderId);

            try {
                $order = Order::byCustomer($user->customer)->where('id', $orderId)->firstOrFail();
                $this->order          = $order;
                $this->workingOnModel = $order;
            } catch (ModelNotFoundException $e) {
                throw new HttpResponseException(redirect('/'));
            }
        }

        $method = PaymentMethod::where('id', $this->order->payment_method_id ?? $this->cart->payment_method_id)->first();

        $this->setVar('methods', PaymentMethod::getAvailableByCart($this->cart));
        $this->setVar('customerMethods', $this->getCustomerMethods());
        $this->setVar('activeMethod', $method);
        $this->setVar('shippingSelectionBeforePayment', GeneralSettings::get('shipping_selection_before_payment', false));	// Needed by themes

        try {
            $paymentData = json_decode(decrypt(session()->get('mall.payment_method.data')), true);
        } catch (DecryptException $e) {
            $paymentData = [];
        }

        $this->setVar('paymentData', $paymentData);
    }

    /**
     * Get the URL to a specific checkout step.
     *
     * @param $step
     * @param null $via
     *
     * @return string
     */
    protected function getStepUrl($step, $via = null): string
    {
        $url = $this->controller->pageUrl($this->page->page->fileName, ['step' => $step]);

        if (! $via) {
            return $url;
        }

        return $url . '?' . http_build_query(['via' => $via]);
    }

    /**
     * Return all CustomerPaymentMethods grouped
     * by the payment method.
     *
     * @return Collection
     */
    protected function getCustomerMethods()
    {
        if (! $this->workingOnModel->customer) {
            return collect([]);
        }

        return optional($this->workingOnModel->customer->payment_methods)->groupBy('payment_method_id');
    }

    /**
     * @param PaymentGateway $gateway
     * @param $data
     *
     * @throws \Cms\Classes\CmsException
     * @return Response|array
     */
    protected function doRedirect(PaymentGateway $gateway, $data)
    {
        // If an order is already available, this is not the normal checkout flow but a
        // subsequent try to pay for an existing order for which the payment failed.
        if ($this->order) {
            // In case the order already exists the payment can be executed directly.
            $paymentService = new PaymentService(
                $gateway,
                $this->order,
                $this->page->page->fileName
            );

            return $paymentService->process('payment');
        }

        // To prevent any data leakage we store payment information encrypted in the session.
        session()->put('mall.payment_method.data', encrypt(json_encode($data)));

        $nextStep = 'confirm';

        if (! $this->shippingSelectionBeforePayment) {
            $nextStep = request()->get('via') === 'confirm' ? 'confirm' : 'shipping';
        }

        $url = $this->getStepUrl($nextStep, 'payment');

        // If the analytics component is present return the datalayer partial that handles the redirect.
        if ($this->page->layout->hasComponent('enhancedEcommerceAnalytics')) {
            return [
                '#mall-datalayer' => $this->renderPartial($this->alias . '::datalayer', ['url' => $url]),
            ];
        }

        return redirect()->to($url);
    }

    /**
     * @return mixed
     */
    protected function getPaymentMethod()
    {
        return PaymentMethod::where('id', $this->workingOnModel->payment_method_id)->firstOrFail();
    }
}
