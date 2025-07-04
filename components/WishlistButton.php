<?php

declare(strict_types=1);

namespace WebBook\Mall\Components;

use Illuminate\Support\Collection;
use October\Rain\Exception\ValidationException;
use October\Rain\Support\Facades\Flash;
use WebBook\Mall\Classes\Traits\HashIds;
use WebBook\Mall\Classes\User\Auth;
use WebBook\Mall\Models\Wishlist;
use WebBook\Mall\Models\WishlistItem;
use Validator;

class WishlistButton extends MallComponent
{
    use HashIds;

    /**
     * All wishlists of this user.
     *
     * @var Collection<Wishlist>
     */
    public $items;

    public function componentDetails()
    {
        return [
            'name'        => 'webbook.mall::lang.components.wishlistButton.details.name',
            'description' => 'webbook.mall::lang.components.wishlistButton.details.description',
        ];
    }

    public function defineProperties()
    {
        return [
            'product' => [
                'title'       => 'webbook.mall::lang.components.wishlistButton.properties.product.title',
                'description' => 'webbook.mall::lang.components.wishlistButton.properties.product.description',
                'type'        => 'string',
            ],
            'variant' => [
                'title'       => 'webbook.mall::lang.components.wishlistButton.properties.variant.title',
                'description' => 'webbook.mall::lang.components.wishlistButton.properties.variant.description',
                'type'        => 'string',
            ],
        ];
    }

    public function init()
    {
        $this->items = $this->page['items'] = $this->getWishlists();
    }

    /**
     * A product is added to a wishlist.
     *
     * @throws ValidationException
     */
    public function onAdd()
    {
        $v = Validator::make(post(), [
            'product_id' => 'required',
            'quantity' => 'nullable|int',
        ]);

        if ($v->fails()) {
            throw new ValidationException($v);
        }

        $wishlists = $this->getWishlists();

        // If there is no wishlist available create the initial one.
        if ($wishlists->count() < 1) {
            $wishlists = collect([Wishlist::createForUser(Auth::user())]);
        }

        $wishlist = post('wishlist_id')
            ? $wishlists->where('id', $this->decode(post('wishlist_id')))->first()
            : $wishlists->first();

        if (! $wishlist) {
            throw new ValidationException(['wishlist_id' => 'Invalid list ID provided.']);
        }

        $quantity = (int)post('quantity', 1);

        if ($quantity < 1) {
            $quantity = 1;
        }

        [$productId, $variantId] = $this->decodeIds();

        WishlistItem::create([
            'product_id' => $productId,
            'quantity' => $quantity,
            'variant_id' => $variantId,
            'wishlist_id' => $wishlist->id,
        ]);

        Flash::success(trans('webbook.mall::frontend.wishlist.added'));

        $this->page['items'] = $this->getWishlists();

        return [
            '.mall-wishlists' => $this->renderPartial($this->alias . '::list', [
                'items' => $this->getWishlists(),
            ]),
        ];
    }

    /**
     * A new wishlist is being created.
     */
    public function onCreate()
    {
        $v = Validator::make(post(), [
            'name' => 'required|max:190',
        ]);

        if ($v->fails()) {
            throw new ValidationException($v);
        }

        $this->decodeIds();

        Wishlist::createForUser(Auth::user(), post('name'));

        return $this->refreshList();
    }

    /**
     * A wishlist is being deleted.
     */
    public function onDelete()
    {
        $v = Validator::make(post(), [
            'name' => 'required|max:190',
        ]);

        if ($v->fails()) {
            throw new ValidationException($v);
        }

        $this->decodeIds();

        Wishlist::where('id', $this->decode(post('wishlist_id')))->delete();

        return $this->refreshList();
    }

    /**
     * Fetches all wishlists of the currently logged in user
     * or the cart session.
     */
    public function getWishlists()
    {
        return Wishlist::byUser(Auth::user());
    }

    /**
     * Re-render the list partial.
     *
     * @return array
     */
    protected function refreshList(): array
    {
        $this->page['items'] = $this->getWishlists();

        return [
            '.mall-wishlists' => $this->renderPartial($this->alias . '::list', ['items' => $this->getWishlists()]),
        ];
    }

    /**
     * @return array
     */
    protected function decodeIds(): array
    {
        $productId = $this->decode(post('product_id'));
        $variantId = post('variant_id') ? $this->decode(post('variant_id')) : null;

        $this->setProperty('product', $productId);
        $this->setProperty('variant', $variantId);

        return [$productId, $variantId];
    }
}
