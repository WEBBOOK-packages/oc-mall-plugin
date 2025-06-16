<?php

declare(strict_types=1);

namespace WebBook\Mall\Components;

use Auth;
use October\Rain\Exception\ValidationException;
use October\Rain\Support\Facades\Flash;
use WebBook\Mall\Models\Cart;
use Throwable;

/**
 * The DiscountApplier component allow the user to enter a discount code.
 */
class DiscountApplier extends MallComponent
{
    /**
     * Component details.
     *
     * @return array
     */
    public function componentDetails()
    {
        return [
            'name'        => 'webbook.mall::lang.components.discountApplier.details.name',
            'description' => 'webbook.mall::lang.components.discountApplier.details.description',
        ];
    }

    /**
     * Properties of this component.
     *
     * @return array
     */
    public function defineProperties()
    {
        return [
            'discountCodeLimit' => [
                'type'    => 'string',
                'title'   => 'webbook.mall::lang.components.cart.properties.discountCodeLimit.title',
                'description' => 'webbook.mall::lang.components.cart.properties.discountCodeLimit.description',
                'default' => 0,
            ],
        ];
    }

    /**
     * A discount code has been entered.
     *
     * Applies the discount code directly to the Cart model.
     *
     * @throws ValidationException
     */
    public function onApplyDiscount()
    {
        $code = strtoupper(post('code'));
        $cart = Cart::byUser(Auth::user());

        try {
            $cart->applyDiscountByCode($code, (int)$this->property('discountCodeLimit'));
        } catch (Throwable $e) {
            throw new ValidationException([
                'code' => $e->getMessage(),
            ]);
        }

        Flash::success(trans('webbook.mall::lang.components.discountApplier.discount_applied'));
    }
}
