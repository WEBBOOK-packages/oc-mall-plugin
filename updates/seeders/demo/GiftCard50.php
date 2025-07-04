<?php

declare(strict_types=1);

namespace WebBook\Mall\Updates\Seeders\Demo;

use WebBook\Mall\Models\ProductPrice;

class GiftCard50 extends DemoProduct
{
    protected function attributes(): array
    {
        return [
            'brand_id'                     => null,
            'user_defined_id'              => 'GIFTCARD50',
            'slug'                         => 'gift-card-50',
            'name'                         => trans('webbook.mall::demo.products.gift_card_50.name'),
            'description'                  => trans('webbook.mall::demo.products.gift_card_50.description'),
            'description_short'            => trans('webbook.mall::demo.products.gift_card_50.description_short'),
            'meta_title'                   => trans('webbook.mall::demo.products.gift_card_50.meta_title'),
            'meta_keywords'                => 'gift, card',
            'weight'                       => 0,
            'inventory_management_method'  => 'product',
            'stock'                        => 100,
            'quantity_default'             => 1,
            'quantity_max'                 => 5,
            'allow_out_of_stock_purchases' => true,
            'links'                        => null,
            'stackable'                    => true,
            'shippable'                    => true,
            'price_includes_tax'           => true,
            'is_virtual'                   => true,
            'mpn'                          => 'GIFTCARD50',
            'published'                    => true,
        ];
    }

    protected function prices(): array
    {
        return [
            new ProductPrice(['currency_id' => 1, 'price' => 40.00]),
            new ProductPrice(['currency_id' => 2, 'price' => 50.00]),
            new ProductPrice(['currency_id' => 3, 'price' => 60.00]),
        ];
    }

    protected function categories(): array
    {
        return [
            $this->category('gift-cards')->id,
        ];
    }

    protected function images(): array
    {
        return [
            [
                'name'        =>  trans('webbook.mall::demo.products.images.gift'),
                'is_main_set' => true,
                'images'      => [
                    realpath(__DIR__ . '/images/gift-card.jpg'),
                ],
            ],
        ];
    }

    protected function properties(): array
    {
        return [];
    }

    protected function variants(): array
    {
        return [];
    }

    protected function customFields(): array
    {
        return [];
    }

    protected function taxes(): array
    {
        return [];
    }
}
