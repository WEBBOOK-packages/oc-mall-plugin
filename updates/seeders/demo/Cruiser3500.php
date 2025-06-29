<?php

declare(strict_types=1);

namespace WebBook\Mall\Updates\Seeders\Demo;

use WebBook\Mall\Models\ProductPrice;

class Cruiser3500 extends DemoProduct
{
    protected function attributes(): array
    {
        return [
            'brand_id'                     => $this->brand('cruiser-bikes')->id,
            'user_defined_id'              => 'MTB003',
            'slug'                         => 'cruiser-3500',
            'name'                         => 'Cruiser 3500',
            'description'                  => trans('webbook.mall::demo.products.cruiser_3500.description'),
            'description_short'            => trans('webbook.mall::demo.products.cruiser_3500.description_short'),
            'meta_title'                   => trans('webbook.mall::demo.products.cruiser_3500.meta_title'),
            'meta_description'             => trans('webbook.mall::demo.products.cruiser_3500.meta_description'),
            'meta_keywords'                => 'mtb, mountainbike, cruiser, bike',
            'weight'                       => 14000,
            'inventory_management_method'  => 'variant',
            'quantity_default'             => 1,
            'quantity_max'                 => 5,
            'allow_out_of_stock_purchases' => false,
            'links'                        => null,
            'stackable'                    => true,
            'shippable'                    => true,
            'price_includes_tax'           => true,
            'mpn'                          => 'CRUISER3500',
            'group_by_property_id'         => $this->property('wheel-size')->id,
            'published'                    => true,
        ];
    }

    protected function taxes(): array
    {
        return [1];
    }

    protected function prices(): array
    {
        return [
            new ProductPrice(['currency_id' => 1, 'price' => 1995]),
            new ProductPrice(['currency_id' => 2, 'price' => 1495]),
            new ProductPrice(['currency_id' => 3, 'price' => 2199]),
        ];
    }

    protected function properties(): array
    {
        return [
            'color'       => [
                'name' => trans('webbook.mall::demo.products.properties.darker_red'),
                'hex' => '#c22a29',
            ],
            'rear-travel' => '0',
            'fork-travel' => '130',
            'material'    => trans('webbook.mall::demo.products.properties.aluminium'),
            'gender'      => trans('webbook.mall::demo.products.properties.unisex'),
        ];
    }

    protected function categories(): array
    {
        return [
            $this->category('mountainbikes')->id,
        ];
    }

    protected function variants(): array
    {
        return [
            [
                'name'       => 'Cruiser 3500 27.5" S',
                'stock'      => 4,
                'prices'     => $this->prices(),
                'properties' => [
                    'frame-size' => 'S (38cm / 15")',
                    'wheel-size' => '27.5"',
                ],
            ],
            [
                'name'       => 'Cruiser 3500 27.5" M',
                'stock'      => 2,
                'properties' => [
                    'frame-size' => 'M (43cm / 17")',
                    'wheel-size' => '27.5"',
                ],
            ],
            [
                'name'       => 'Cruiser 3500 27.5" L',
                'stock'      => 0,
                'properties' => [
                    'frame-size' => 'L (48cm / 19")',
                    'wheel-size' => '27.5"',
                ],
            ],
            [
                'name'       => 'Cruiser 3500 29" S',
                'stock'      => 1,
                'properties' => [
                    'frame-size' => 'S (38cm / 15")',
                    'wheel-size' => '29"',
                ],
            ],
            [
                'name'       => 'Cruiser 3500 29" M',
                'stock'      => 8,
                'properties' => [
                    'frame-size' => 'M (43cm / 17")',
                    'wheel-size' => '29"',
                ],
            ],
            [
                'name'       => 'Cruiser 3500 29" L',
                'stock'      => 5,
                'properties' => [
                    'frame-size' => 'L (48cm / 19")',
                    'wheel-size' => '29"',
                ],
            ],
        ];
    }

    protected function customFields(): array
    {
        return [
            [
                'name'     =>  trans('webbook.mall::demo.products.fields.include_bike_assembly'),
                'type'     => 'checkbox',
                'price'    => ['USD' => 490, 'EUR' => 200, 'CHF' => 5900],
                'required' => false,
            ],
        ];
    }

    protected function images(): array
    {
        return [
            [
                'name'          =>  trans('webbook.mall::demo.products.images.main'),
                'is_main_set'   => true,
                'images'        => [
                    realpath(__DIR__ . '/images/cruiser-3500-1.jpg'),
                    realpath(__DIR__ . '/images/cruiser-5000-2.jpg'),
                ],
            ],
        ];
    }
}
