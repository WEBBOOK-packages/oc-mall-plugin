<?php

declare(strict_types=1);

namespace WebBook\Mall\Components;

use Auth;
use Illuminate\Support\Collection;
use October\Rain\Exception\ValidationException;
use October\Rain\Support\Facades\Flash;
use WebBook\Mall\Models\Address;
use WebBook\Mall\Models\Cart;
use WebBook\Mall\Models\GeneralSettings;

/**
 * Display a list of user addresses.
 */
class AddressList extends MallComponent
{
    /**
     * All available countries.
     *
     * @var Collection
     */
    public $countries;

    /**
     * All of the user's addresses.
     *
     * @var Collection
     */
    public $addresses;

    /**
     * The name of the address edit page.
     *
     * @var string
     */
    public $addressPage;

    /**
     * The id of the default billing address.
     *
     * @var int
     */
    public $defaultBillingAddressId;

    /**
     * The id of the default shipping address.
     *
     * @var int
     */
    public $defaultShippingAddressId;

    /**
     * Component details.
     *
     * @return array
     */
    public function componentDetails()
    {
        return [
            'name'        => 'webbook.mall::lang.components.addressList.details.name',
            'description' => 'webbook.mall::lang.components.addressList.details.description',
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
        if ($user = Auth::user()) {
            $this->addresses = $user->customer->addresses;
            $this->defaultBillingAddressId = $user->customer->default_billing_address_id;
            $this->defaultShippingAddressId = $user->customer->default_shipping_address_id;
            $this->addressPage = GeneralSettings::get('address_page');
        }
    }

    /**
     * @throws ValidationException
     * @return array
     */
    public function onChangeDefaultShippingAddress()
    {
        $this->updateDefaultAddressFromUser('shipping');

        Flash::success(trans('webbook.mall::lang.components.addressList.messages.default_shipping_address_changed'));

        return [
            '.mall-address-list__list' => $this->renderPartial($this->alias . '::list'),
        ];
    }

    /**
     * @throws ValidationException
     * @return array
     */
    public function onChangeDefaultBillingAddress()
    {
        $this->updateDefaultAddressFromUser('billing');

        Flash::success(trans('webbook.mall::lang.components.addressList.messages.default_billing_address_changed'));

        return [
            '.mall-address-list__list' => $this->renderPartial($this->alias . '::list'),
        ];
    }

    /**
     * @param string $type
     * @throws ValidationException
     * @return mixed
     */
    public function updateDefaultAddressFromUser(string $type)
    {
        $id = $this->decode(post('id'));
        $user = Auth::user();
        $customer = $user->customer;
        $cart = Cart::byUser($user);

        $address = Address::byCustomer($customer)->where('id', $id)->first();

        if (!$address) {
            throw new ValidationException(['id' => trans('webbook.mall::lang.components.addressList.errors.address_not_found')]);
        }

        if ($type === 'billing') {
            $this->defaultBillingAddressId = $customer->default_billing_address_id = $id;
            $cart->setBillingAddress($address);
        } elseif ($type === 'shipping') {
            $this->defaultShippingAddressId = $customer->default_shipping_address_id = $id;
            $cart->setShippingAddress($address);
        }

        $cart->save();

        return $customer->save();
    }

    /**
     * The user deleted an address.
     *
     * @throws ValidationException
     * @return array
     */
    public function onDelete()
    {
        $id = $this->decode(post('id'));
        $user = Auth::user();
        $customer = $user->customer;
        $cart = Cart::byUser($user);
        $address = Address::byCustomer($customer)->where('id', $id)->first();

        if (!$address) {
            throw new ValidationException(['id' => trans('webbook.mall::lang.components.addressList.errors.address_not_found')]);
        }

        if (Address::byCustomer($customer)->count() <= 1) {
            throw new ValidationException(['id' => trans('webbook.mall::lang.components.addressList.errors.cannot_delete_last_address')]);
        }

        $address->delete();
        $this->addresses = Auth::user()->load('customer')->customer->addresses;

        $defaultAddress = Address::byCustomer($customer)->first();

        if ($customer->default_shipping_address_id === $address->id) {
            $customer->default_shipping_address_id = $defaultAddress->id;
            $cart->setShippingAddress($defaultAddress);
        }

        if ($customer->default_billing_address_id === $address->id) {
            $customer->default_billing_address_id = $defaultAddress->id;
            $cart->setBillingAddress($defaultAddress);
        }

        $customer->save();

        Flash::success(trans('webbook.mall::lang.components.addressList.messages.address_deleted'));

        return [
            '.mall-address-list__list' => $this->renderPartial($this->alias . '::list'),
        ];
    }
}
