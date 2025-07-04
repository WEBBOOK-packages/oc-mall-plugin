<?php

namespace WebBook\Mall\Classes\Observers;

use WebBook\Mall\Classes\Index\Index;
use WebBook\Mall\Models\ProductPrice;

class ProductPriceObserver
{
    protected $index;

    public function __construct(Index $index)
    {
        $this->index = $index;
    }

    public function created(ProductPrice $price)
    {
        $this->handle($price);
    }

    public function updated(ProductPrice $price)
    {
        $this->handle($price);
    }

    public function deleted(ProductPrice $price)
    {
        $this->handle($price);
    }

    protected function handle(ProductPrice $price)
    {
        if ($price->product) {
            (new ProductObserver($this->index))->updated($price->product);
        }

        if ($price->variant) {
            (new VariantObserver($this->index))->updated($price->variant);
        }
    }
}
