<?php

namespace WebBook\Mall\Classes\Index;

use Event;
use Illuminate\Support\Collection;
use WebBook\Mall\Models\Currency;
use WebBook\Mall\Models\CustomerGroup;
use WebBook\Mall\Models\Product;

class ProductEntry implements Entry
{
    public const INDEX = 'products';

    protected $product;

    protected $data;

    public function __construct(Product $product)
    {
        $this->product = $product;

        // Make sure variants inherit product data again.
        session()->forget('mall.variants.disable-inheritance');

        $data                = $product->getAttributes();
        $data['created_at'] = optional($product->created_at)->format('Y-m-d H:i:s');
        $data['index']       = self::INDEX;
        $data['on_sale']     = $product->on_sale;
        $data['category_id'] = $product->categories->pluck('id');

        $data['property_values']       = $this->mapProps($product->property_values);
        $data['prices']                = $this->mapPrices($product);
        $data['customer_group_prices'] = $this->mapCustomerGroupPrices($product);

        if ($product->brand) {
            $data['brand'] = ['id' => $product->brand->id, 'slug' => $product->brand->slug];
        }

        $data['sort_orders'] = $product->getSortOrders();

        $result = Event::fire('mall.index.extendProduct', [$product]);

        if ($result && is_array($result) && $filtered = array_filter($result)) {
            $this->data = array_merge(...$filtered) + $data;
        } else {
            $this->data = $data;
        }
    }

    public function data(): array
    {
        return $this->data;
    }

    public function withData(array $data): Entry
    {
        $this->data = array_merge($this->data, $data);

        return $this;
    }

    protected function mapPrices(Product $product): Collection
    {
        return $product->withForcedPriceInheritance(fn () => Currency::get()->mapWithKeys(fn ($currency) => [$currency->code => $product->price($currency)->integer]));
    }

    protected function mapCustomerGroupPrices($model): Collection
    {
        return CustomerGroup::get()->mapWithKeys(function ($group) use ($model) {
            return [
                $group->id => Currency::get()->mapWithKeys(function ($currency) use ($model, $group) {
                    $price = $model->groupPrice($group, $currency);

                    if ($price) {
                        return [$price->currency->code => $price->integer];
                    }

                    return null;
                })->filter(),
            ];
        });
    }

    protected function mapProps(?Collection $input): Collection
    {
        if ($input === null) {
            return collect();
        }

        return $input->groupBy('property_id')->map(fn ($value) => $value->pluck('index_value')->unique()->filter(fn ($item) => !empty($item) || $item === 0 || $item === '0')->values())->filter();
    }
}
