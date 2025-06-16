<?php

namespace WebBook\Mall\Classes\Jobs;

use Illuminate\Contracts\Queue\Job;
use WebBook\Mall\Classes\Index\Index;
use WebBook\Mall\Classes\Observers\ProductObserver;
use WebBook\Mall\Models\Product;

class BrandChangeUpdate
{
    public function fire(Job $job, $data)
    {
        if ($job->attempts() > 5) {
            logger()->error('Failed to handle brand change. Please run php artisan mall:index manually to update your index');
            $job->delete();
        }

        $index = app(Index::class);

        Product::whereIn('id', $data['ids'] ?? [])
            ->each(function (Product $product) use ($index) {
                (new ProductObserver($index))->updated($product);
            });

        $job->delete();
    }
}
