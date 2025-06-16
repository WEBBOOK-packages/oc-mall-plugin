<?php

declare(strict_types=1);

namespace WebBook\Mall\Classes\Exceptions;

use Illuminate\Support\Collection;
use WebBook\Mall\Models\Cart;
use WebBook\Mall\Models\Discount;
use RuntimeException;

class InvalidDiscountException extends RuntimeException
{
    /**
     * @var Cart
     */
    public $cart;
    /**
     * @var Collection<Discount>
     */
    public $discounts;

    /**
     * Create a new exception.
     */
    public function __construct(Cart $cart, Collection $discounts)
    {
        $this->cart = $cart;
        $this->discounts = $discounts;

        parent::__construct(
            'Used discounts are no longer valid.',
            422
        );
    }
}
