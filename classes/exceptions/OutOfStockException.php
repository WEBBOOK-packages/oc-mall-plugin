<?php

declare(strict_types=1);

namespace WebBook\Mall\Classes\Exceptions;

use WebBook\Mall\Models\Product;
use WebBook\Mall\Models\Variant;
use RuntimeException;

class OutOfStockException extends RuntimeException
{
    /**
     * The product which is out of stock.
     * @var Product|Variant
     */
    public $product;

    /**
     * Create a new exception.
     * @param Product|Variant $product
     */
    public function __construct($product)
    {
        $this->product = $product;
        parent::__construct(
            sprintf('The product %s is currently not in stock.', $this->product->name),
            422
        );
    }
}
