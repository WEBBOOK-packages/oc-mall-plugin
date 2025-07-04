<?php

declare(strict_types=1);

namespace WebBook\Mall\Components;

use Cms\Classes\ComponentBase;
use DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use WebBook\Mall\Classes\User\Auth;
use WebBook\Mall\Models\CategoryReview;
use WebBook\Mall\Models\GeneralSettings;
use WebBook\Mall\Models\Product as ProductModel;
use WebBook\Mall\Models\Review;
use WebBook\Mall\Models\ReviewCategory;
use WebBook\Mall\Models\ReviewSettings;

class ProductReviews extends ComponentBase
{
    /**
     * @var ProductModel
     */
    public $product;

    /**
     * @var Collection<Review>
     */
    public $reviews;

    /**
     * @var Collection<Review>
     */
    public $allReviews;

    /**
     * @var Collection<ReviewCategory>
     */
    public $reviewCategories;

    /**
     * @var Review
     */
    public $customerReview;

    /**
     * @var string
     */
    public $accountPage;

    /**
     * Determines whether the current user can create a new review.
     * @var bool
     */
    public $canReview;

    /**
     * @var bool
     */
    public $isModerated;

    public function componentDetails()
    {
        return [
            'name'        => 'webbook.mall::lang.components.productReviews.details.name',
            'description' => 'webbook.mall::lang.components.productReviews.details.description',
        ];
    }

    public function defineProperties()
    {
        return [
            'product'                   => [
                'title'       => 'webbook.mall::lang.components.wishlistButton.properties.product.title',
                'description' => 'webbook.mall::lang.components.wishlistButton.properties.product.description',
                'type'        => 'string',
            ],
            'variant'                   => [
                'title'       => 'webbook.mall::lang.components.wishlistButton.properties.variant.title',
                'description' => 'webbook.mall::lang.components.wishlistButton.properties.variant.description',
                'type'        => 'string',
            ],
            'perPage'                   => [
                'title' => 'webbook.mall::lang.components.productReviews.properties.perPage.title',
                'type'  => 'string',
            ],
            'currentVariantReviewsOnly' => [
                'title'       => 'webbook.mall::lang.components.productReviews.properties.currentVariantReviewsOnly.title',
                'description' => 'webbook.mall::lang.components.productReviews.properties.currentVariantReviewsOnly.description',
                'type'        => 'checkbox',
                'default'     => 0,
            ],
        ];
    }

    public function setData()
    {
        $this->product          = ProductModel::where('id', $this->property('product'))->firstOrFail();
        $this->reviewCategories = $this->product->categories->flatMap->inherited_review_categories->unique('id');
        $this->accountPage      = GeneralSettings::get('account_page');
        $this->isModerated      = ReviewSettings::get('moderated');

        if (Auth::user()) {
            $this->canReview = true;
        } else {
            $this->canReview = ReviewSettings::get('allow_anonymous', false);
        }

        $limitToVariant = (bool)$this->property('currentVariantReviewsOnly') && (bool)$this->property('variant');

        $this->allReviews = Review::with(['category_reviews.review_category.translations', 'variant'])
            ->where('product_id', $this->product->id)
            ->when($limitToVariant, function ($q) {
                $q->where('variant_id', $this->property('variant'));
            })
            ->whereNotNull('approved_at')
            ->orderBy('created_at', 'DESC')
            ->get();

        $this->customerReview = Review::with('category_reviews')
            ->where('product_id', $this->product->id)
            ->when($this->property('variant'), function ($q) {
                $q->where('variant_id', $this->property('variant'));
            })
            ->when(optional(Auth::user())->customer, function ($q) {
                $q->where('customer_id', Auth::user()->customer->id);
            }, function ($q) {
                $q->where('user_hash', Review::getUserHash());
            })
            ->first();

        $pageNumber    = (int)input('page', 1);
        $perPage       = (int)$this->property('perPage', 5);
        $slice         = $this->allReviews->slice(($pageNumber - 1) * $perPage, $perPage);
        $this->reviews = new LengthAwarePaginator($slice, $this->allReviews->count(), $perPage, $pageNumber);
    }

    public function onRun()
    {
        $this->setData();
    }

    public function onPageChange()
    {
        $this->setData();

        return [
            '.mall-reviews' => $this->renderPartial($this->alias . '::reviews', [
                'reviews' => $this->reviews,
            ]),
        ];
    }

    public function onCreate()
    {
        $this->setData();

        DB::transaction(function () {
            $data = $this->getInputData();
            // Create the main review.
            $review = new Review();
            $review->fill($data);
            $review->product_id  = $this->property('product');
            $review->variant_id  = $this->property('variant');
            $review->customer_id = optional(optional(Auth::user())->customer)->id;

            if (! $this->isModerated) {
                $review->approved_at = now();
            }
            $review->save();
            // Store any category reviews that are available.
            $categoryRatings = array_filter(post('category_rating', []));

            if (is_array($categoryRatings) && count($categoryRatings) > 0) {
                $approvedAt = $this->isModerated ? null : now();
                $this->reviewCategories->each(function (ReviewCategory $category) use (
                    $review,
                    $categoryRatings,
                    $approvedAt
                ) {
                    if ($value = array_get($categoryRatings, $category->id)) {
                        CategoryReview::create([
                            'review_id'          => $review->id,
                            'review_category_id' => $category->id,
                            'rating'             => $value,
                            'approved_at'        => $approvedAt,
                        ]);
                    }
                });
            }

            return $review;
        });

        // Refetch latest data.
        $this->setData();

        return $this->refreshFormAndList(true);
    }

    public function onUpdate()
    {
        $this->setData();

        DB::transaction(function () {
            $data = $this->getInputData();
            // Update the main review.
            $review = $this->customerReview;
            $review->fill($data);
            $review->save();
            // Update any category reviews that are available.
            $categoryRatings = array_filter(post('category_rating', []));

            if (is_array($categoryRatings) && count($categoryRatings) > 0) {
                $this->reviewCategories->each(function (ReviewCategory $category) use ($review, $categoryRatings) {
                    if ($value = array_get($categoryRatings, $category->id)) {
                        // Fetch an existing rating and update it.
                        $categoryReview = CategoryReview::where([
                            'review_id'          => $review->id,
                            'review_category_id' => $category->id,
                        ])->first();

                        if ($categoryReview) {
                            return $categoryReview->load(['review.product', 'review.variant'])->update(['rating' => $value]);
                        }

                        // If there is no review for this category, create it now.
                        $approvedAt = $this->isModerated ? null : now();
                        CategoryReview::create([
                            'review_id'          => $review->id,
                            'review_category_id' => $category->id,
                            'rating'             => $value,
                            'approved_at'        => $approvedAt,
                        ]);
                    }
                });
            }

            return $review;
        });

        // Refetch latest data.
        $this->setData();

        return $this->refreshFormAndList(false);
    }

    /**
     * @param bool $new
     *
     * @return array
     */
    protected function refreshFormAndList(bool $new): array
    {
        return [
            '#mall-rating-widget' => $this->renderPartial($this->alias . '::okay', ['isNew' => $new]),
            '.mall-reviews'       => $this->renderPartial($this->alias . '::reviews', [
                'reviews' => $this->reviews,
            ]),
        ];
    }

    /**
     * Returns the form input data.
     * @return array
     */
    protected function getInputData(): array
    {
        $data = post();

        // Check if input contains something other than whitespace.
        $hasContent = fn ($input) => $input !== '' && ctype_space($input) === false;
        // Since the data is stored in a repeater field, we need to add a value key.
        $addValueKey = fn ($value) => ['value' => $value];

        // Split up on new lines.
        $pros = explode("\n", post('pros', ''));
        $cons = explode("\n", post('cons', ''));

        // Filter out empty lines.
        $pros = array_filter($pros, $hasContent);
        $cons = array_filter($cons, $hasContent);

        // Add the value key.
        $data['pros'] = array_map($addValueKey, $pros);
        $data['cons'] = array_map($addValueKey, $cons);

        return $data;
    }
}
